<?php

namespace Afup\BarometreBundle\Controller;

use Afup\Barometre\Report\AlterableReportInterface;
use Afup\Barometre\ReportManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class ReportController extends Controller
{
    /**
     * @param Request $request
     * @param string  $reportName
     *
     * @Template
     *
     * @return array
     */
    public function indexAction(Request $request, $reportName)
    {
        $masterRequest = $this->get('request_stack')->getMasterRequest();

        $manager = $this->getManager();
        $manager->handleRequest($masterRequest);

        $report = $manager->getReport($reportName);

        $report->execute();

        $childReports = [];
        foreach ($report->getChildReports() as $childReport) {

            if ($childReport instanceof AlterableReportInterface) {
                $request = clone $masterRequest;
                $childReport->alterRequest($request);
            }

            $childManager = $this->getManager();
            $childManager->handleRequest($request);

            $childReport->setQueryBuilder($childManager->createBaseQueryBuilder());
            $childReport->execute();
            $childReports[] = [
                'filters' => $childManager->getSelectedFilters(),
                'report' => $childReport
            ];
        }

        return [
            'form'    => $manager->getForm()->createView(),
            'filters' => $manager->getSelectedFilters(),
            'report'  => $report,
            'child_reports' => $childReports,
        ];
    }

    /**
     * @return ReportManager
     */
    private function getManager()
    {
        return $this->get('afup.barometre.manager');
    }
}
