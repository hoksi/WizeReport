<?php

/**
 * Created by IntelliJ IDEA.
 * User: hoksi
 * Date: 2017-06-20
 * Time: 오전 7:02
 */
class Gareport
{
    protected $profile_id;
    protected $current;
    protected $previous;
    protected $range7;
    protected $range6;
    protected $gauth;

    public function __construct()
    {
        if (isset($_SESSION['profile_id'])) {
            $this->profile_id = $_SESSION['profile_id'];

            $ci = &get_instance();
            $ci->load->library('gauth');

            if (true) {
                $this->gauth = &$ci->gauth;
            } else {
                $this->gauth = new Gauth();
            }
        } else {
            redirect('welcome/login');
        }
    }

    public function setCurrent($syear, $smonth, $sday, $eyear, $emonth, $eday)
    {
        $this->current['start'] = sprintf('%04d-%02d-%02d', $syear, $smonth, $sday);
        $this->current['end'] = sprintf('%04d-%02d-%02d', $eyear, $emonth, $eday);

        $timestamp = mktime(0, 0, 0, $smonth - 6, 1, $syear);
        $this->range7['start'] = date('Y-m-01', $timestamp);
        $this->range7['end'] = $this->current['end'];

        $timestamp = mktime(0, 0, 0, $smonth - 5, 1, $syear);
        $this->range6['start'] = date('Y-m-01', $timestamp);
        $this->range6['end'] = $this->current['end'];

        return $this;
    }

    public function setPrevious($year, $month, $day)
    {
        $this->previous['start'] = sprintf('%04d-%02d-%02d', $year, $month, $day);

        $startDate = new DateTime($this->current['start']);
        $endDate = new DateTime($this->current['end']);
        $dateInterval = $startDate->diff($endDate);

        $timestamp = mktime(0, 0, 0, $month, ($day + $dateInterval->days), $year);
        $this->previous['end'] = date('Y-m-d', $timestamp);

        return $this;
    }

    public function getCurrent()
    {
        return $this->current;
    }

    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * 전체요약 리포트
     * @return array
     */
    public function overallSummary()
    {
        $current_month_summary = $this->gauth->getResults($this->profile_id, 'current_month_summary', $this->current['start'], $this->current['end']);
        $previous_month_summary = $this->gauth->getResults($this->profile_id, 'previous_month_summary', $this->previous['start'], $this->previous['end']);
        $current_month_visitor_acquisition_all = $this->gauth->getResults($this->profile_id, 'current_month_visitor_acquisition_all', $this->current['start'], $this->current['end']);
        $previous_month_visitor_acquisition_all = $this->gauth->getResults($this->profile_id, 'previous_month_visitor_acquisition_all', $this->previous['start'], $this->previous['end']);
        $visitor_sales_trend = $this->gauth->getResults($this->profile_id, 'visitor_sales_trend', $this->range7['start'], $this->range7['end']);

        // 그래프용 데이터
        $monthSession = array();
        $monthRevenue = array();

        foreach ($visitor_sales_trend as $data) {
            $monthSession[] = array($data[0], $data[1]);
            $monthRevenue[] = array($data[0], $data[2]);
        }

        $report = array(
            '방문수' => $current_month_summary[0][1],
            '방문수증감' => $current_month_summary[0][1] - $previous_month_summary[0][1],
            '매출' => $current_month_summary[0][0],
            '매출증감' => $current_month_summary[0][0] - $previous_month_summary[0][0],
            '검색' => $current_month_visitor_acquisition_all[1][1],
            '검색증감' => $current_month_visitor_acquisition_all[1][1] - $previous_month_visitor_acquisition_all[1][1],
            '링크' => $current_month_visitor_acquisition_all[2][1],
            '링크증감' => $current_month_visitor_acquisition_all[2][1] - $previous_month_visitor_acquisition_all[2][1],
            '직접주소입력' => $current_month_visitor_acquisition_all[0][1],
            '직접주소입력증감' => $current_month_visitor_acquisition_all[0][1] - $previous_month_visitor_acquisition_all[0][1],
            '소셜네트워크' => $current_month_visitor_acquisition_all[3][1],
            '소셜네트워크증감' => $current_month_visitor_acquisition_all[3][1] - $previous_month_visitor_acquisition_all[3][1],
            '결재' => $current_month_summary[0][2],
            '결재증감' => $current_month_summary[0][2] - $previous_month_summary[0][2],
            '결제전환율' => round($current_month_summary[0][3], 2),
            '결제전환율증감' => round($current_month_summary[0][3] - $previous_month_summary[0][3], 2),
            '평균결제액' => round($current_month_summary[0][5], 0),
            '평균결제액증감' => round($current_month_summary[0][5] - $previous_month_summary[0][5], 0),
            '월별방문' => $monthSession,
            '월별매출' => $monthRevenue,
        );

        return $report;
    }

    /**
     * 캠페인 리포트
     * @return array
     */
    public function campaign()
    {
        $current_month_campaign = $this->gauth->getResults($this->profile_id, 'current_month_campaign', $this->current['start'], $this->current['end']);
        $previous_month_campaign = $this->gauth->getResults($this->profile_id, 'previous_month_campaign', $this->previous['start'], $this->previous['end']);
        $campaign_trend = $this->gauth->getResults($this->profile_id, 'campaign_trend', $this->range7['start'], $this->range7['end']);

        // Total
        $totalCurrentSession = 0;
        $totalCurrentRevenue = 0;
        $maxRevenue = array(
            '캠페인' => '',
            '매출' => 0,
            '매출비중' => 0,
            '방문수' => 0,
            '구매비율' => 0,
            '평균주문액' => 0,
            '건당평균주문액' => 0
        );
        $maxPurchase = $maxRevenue;
        $maxRevenuePerSession = $maxRevenue;

        $minRevenue = array(
            '캠페인' => '',
            '매출' => 9999999,
            '매출비중' => 9999999,
            '방문수' => 9999999,
            '구매비율' => 9999999,
            '평균주문액' => 9999999,
            '건당평균주문액' => 9999999
        );
        $minPurchase = $minRevenue;
        $minRevenuePerSession = $minRevenue;

        if (!empty($current_month_campaign)) {
            foreach ($current_month_campaign as $citem) {
                $totalCurrentRevenue += $citem[2];
                $totalCurrentSession += $citem[4];

                // 매출액이 가장 높은 캠페인
                if ($citem[2] > $maxRevenue['매출']) {
                    $maxRevenue = array(
                        '캠페인' => $citem[0],
                        '매출' => $citem[2],
                        '매출비중' => $citem[2],
                        '방문수' => $citem[3],
                        '구매비율' => round($citem[5], 2),
                        '평균주문액' => $citem[6],
                        '건당평균주문액' => $citem[7]
                    );
                }

                // 매출액이 가장 낮은 캠페인
                if ($citem[2] <= $minRevenue['매출']) {
                    $minRevenue = array(
                        '캠페인' => $citem[0],
                        '매출' => $citem[2],
                        '매출비중' => $citem[2],
                        '방문수' => $citem[3],
                        '구매비율' => round($citem[5], 2),
                        '평균주문액' => $citem[6],
                        '건당평균주문액' => $citem[7]
                    );
                }

                // 구매비율이 가장 높은 캠페인
                if ($citem[5] > $maxPurchase['구매비율']) {
                    $maxPurchase = array(
                        '캠페인' => $citem[0],
                        '매출' => $citem[2],
                        '매출비중' => $citem[2],
                        '방문수' => $citem[3],
                        '구매비율' => round($citem[5], 2),
                        '평균주문액' => $citem[6],
                        '건당평균주문액' => $citem[7]
                    );
                }

                // 구매비율이 가장 낮은 캠페인
                if ($citem[5] <= $minPurchase['구매비율']) {
                    $minPurchase = array(
                        '캠페인' => $citem[0],
                        '매출' => $citem[2],
                        '매출비중' => $citem[2],
                        '방문수' => $citem[3],
                        '구매비율' => round($citem[5], 2),
                        '평균주문액' => $citem[6],
                        '건당평균주문액' => $citem[7]
                    );
                }

                // 건당 평균 주문액이 가장 높은 캠페인
                if ($citem[7] > $maxRevenuePerSession['건당평균주문액']) {
                    $maxRevenuePerSession = array(
                        '캠페인' => $citem[0],
                        '매출' => $citem[2],
                        '매출비중' => $citem[2],
                        '방문수' => $citem[3],
                        '구매비율' => round($citem[5], 2),
                        '평균주문액' => $citem[6],
                        '건당평균주문액' => $citem[7]
                    );
                }

                // 건당 평균 주문액이 가장 높은 캠페인
                if ($citem[7] <= $minRevenuePerSession['건당평균주문액']) {
                    $minRevenuePerSession = array(
                        '캠페인' => $citem[0],
                        '매출' => $citem[2],
                        '매출비중' => $citem[2],
                        '방문수' => $citem[3],
                        '구매비율' => round($citem[5], 2),
                        '평균주문액' => $citem[6],
                        '건당평균주문액' => $citem[7]
                    );
                }
            }

            $maxRevenue['매출비중'] = round($maxRevenue['매출비중'] / $totalCurrentRevenue * 100, 2);
            $maxPurchase['매출비중'] = round($maxPurchase['매출비중'] / $totalCurrentRevenue * 100, 2);

            $totalPreviousSession = 0;
            $totalPreviouRevenue = 0;
            foreach ($previous_month_campaign as $citem) {
                $totalPreviouRevenue += $citem[2];
                $totalPreviousSession += $citem[4];
            }

            // 그래프용 데이터
            $monthSession = array();
            $monthRevenue = array();

            foreach ($campaign_trend as $data) {
                $monthRevenue[] = array($data[0], $data[1]);
                $monthSession[] = array($data[0], $data[2]);
            }

            $report = array(
                '광고캠페인방문수' => $totalCurrentSession,
                '광고캠페인매출' => $totalCurrentRevenue,
                '광고캠페인방문수증감' => $totalCurrentSession - $totalPreviousSession,
                '광고캠페인매출증감' => $totalCurrentRevenue - $totalPreviouRevenue,
                '광고캠페인방문그래프' => $monthSession,
                '광고캠페인매출그래프' => $monthSession,
                '매출가장높은캠페인' => $maxRevenue,
                '구매율가장높은캠페인' => $maxPurchase,
                '건당평균주문액가장높은캠페인' => $maxRevenuePerSession,
                '매출가장낮은캠페인' => $minRevenue,
                '구매율가장낮은캠페인' => $minPurchase,
                '건당평균주문액가장낮은캠페인' => $minRevenuePerSession
            );

            return $report;
        } else {
            return false;
        }
    }

    /**
     * 전체 캠페인 리포트
     * @return array|bool
     */
    public function campaingnAll()
    {
        $current_month_campaign = $this->gauth->getResults($this->profile_id, 'current_month_campaign', $this->current['start'], $this->current['end']);

        if (!empty($current_month_campaign)) {
            $report = array();

            foreach ($current_month_campaign as $item) {
                $report[] = array(
                    '캠페인' => $item[0],
                    '매출' => $item[2],
                    '방문수' => $item[4],
                    '구매비율' => round($item[5], 2),
                    '평균주문액' => $item[6]
                );
            }

            return $report;
        } else {
            return false;
        }
    }

    /**
     * 검색 키워드
     * @return array|bool
     */
    public function keywordNew()
    {
        $current_month_keyword = $this->gauth->getResults($this->profile_id, 'current_month_keyword', $this->current['start'], $this->current['end']);
        $previous_month_keyword = $this->gauth->getResults($this->profile_id, 'previous_month_keyword', $this->previous['start'], $this->previous['end']);

        if (!empty($current_month_keyword)) {
            $report = array();
            $previous_keyword = array();

            foreach ($previous_month_keyword as $item) {
                $previous_keyword[$item[0]] = true;

                if ($item[1] == 1) {
                    $report['유입량없는이전키워드'][] = array(
                        '키워드' => $item[0],
                        '유입량' => $item[1]
                    );
                }
            }

            foreach ($current_month_keyword as $item) {
                if (!isset($previous_keyword[$item[0]])) {
                    $report['신규유입키워드'][] = array(
                        '키워드' => $item[0],
                        '유입건수' => $item[1],
                        '매출' => $item[2]
                    );
                }
            }

            return $report;
        } else {
            return false;
        }
    }

    /**
     * 검색 키워드 전체
     * @return array|bool
     */
    public function keywordAll()
    {
        $current_month_keyword = $this->gauth->getResults($this->profile_id, 'current_month_keyword', $this->current['start'], $this->current['end']);
        $previous_month_keyword = $this->gauth->getResults($this->profile_id, 'previous_month_keyword', $this->previous['start'], $this->previous['end']);

        if (!empty($current_month_keyword)) {
            $report = array();
            $previous_keyword = array();

            foreach ($previous_month_keyword as $item) {
                $previous_keyword[$item[0]] = array(
                    '유입수' => $item[1],
                    '매출' => $item[2]
                );
            }

            foreach ($current_month_keyword as $item) {
                $report[] = array(
                    '키워드' => $item[0],
                    '유입건수' => $item[1],
                    '유입증감' => (isset($previous_keyword[$item[0]]['유입수']) ? $item[1] - $previous_keyword[$item[0]]['유입수'] : 'new'),
                    '매출' => $item[2],
                    '매출증감' => $item[2] - (isset($previous_keyword[$item[0]]['매출']) ? $previous_keyword[$item[0]]['매출'] : 0),
                );
            }

            return $report;
        } else {
            return false;
        }
    }

    /**
     * 네이버 키워드
     * @return array|bool
     */
    public function naverKeyword()
    {
        $current_naver_keywords_advertising = $this->gauth->getResults($this->profile_id, 'current_naver_keywords_advertising', $this->current['start'], $this->current['end']);
        $previous_naver_keywords_advertising = $this->gauth->getResults($this->profile_id, 'previous_naver_keywords_advertising', $this->previous['start'], $this->previous['end']);

        if (!empty($current_naver_keywords_advertising)) {
            $report = array();
            $previous = array();
            $current = array();
            $sort = array();

            foreach ($previous_naver_keywords_advertising as $item) {
                $tmp = explode('n_keyword=', $item[0]);
                $keyword = explode('&', $tmp[1]);
                if (isset($previous[$keyword[0]])) {
                    $previous[$keyword[0]]['유입수'] += $item[1];
                    $previous[$keyword[0]]['매출'] += $item[3];
                    $sort[$keyword[0]] += $item[1];
                } else {
                    $previous[$keyword[0]] = array(
                        '키워드' => $keyword[0],
                        '유입수' => $item[1],
                        '매출' => $item[3]
                    );
                    $sort[$keyword[0]] = $item[1];
                }
            }

            arsort($sort);
            foreach ($sort as $key => $val) {
                $report['이전'][] = $previous[$key];
            }

            $sort = array();
            foreach ($current_naver_keywords_advertising as $item) {
                $tmp = explode('n_keyword=', $item[0]);
                $keyword = explode('&', $tmp[1]);
                if (isset($current[$keyword[0]])) {
                    $current[$keyword[0]]['유입수'] += $item[1];
                    $current[$keyword[0]]['매출'] += $item[3];
                    $sort[$keyword[0]] += $item[1];
                } else {
                    $current[$keyword[0]] = array(
                        '키워드' => $keyword[0],
                        '유입수' => $item[1],
                        '매출' => $item[3]
                    );
                    $sort[$keyword[0]] = $item[1];
                }
            }

            arsort($sort);
            foreach ($sort as $key => $val) {
                $current[$key]['유입증감'] = $current[$key]['유입수'] - (isset($previous[$key]) ? $previous[$key]['유입수'] : 0);
                $current[$key]['매출증감'] = $current[$key]['매출'] - (isset($previous[$key]) ? $previous[$key]['매출'] : 0);
                $report['현재'][] = $current[$key];
            }

            return $report;
        } else {
            return false;
        }
    }

    /**
     * 상품분석
     * @return array|bool
     */
    public function product()
    {
        $product_trends = $this->gauth->getResults($this->profile_id, 'product_trends', $this->range7['start'], $this->current['end']);
        $product = $this->gauth->getResults($this->profile_id, 'product', $this->current['start'], $this->current['end']);

        if (!empty($product_trends)) {
            $product_name = array();

            foreach ($product_trends as $item) {
                $product_name[$item[0]][$item[1]] = array(
                    '매출' => $item[6]
                );
            }

            $report = array(
                'product_name' => $product_name,
                'product_trand' => $product_trends,
                'product' => $product
            );

            return $report;
        } else {
            return false;
        }
    }

    /**
     * 방문통계 요약
     * @return array|bool
     */
    public function visitStat()
    {
        $visitor_acquisition_graph = $this->gauth->getResults($this->profile_id, 'visitor_acquisition_graph', $this->range6['start'], $this->current['end']);
        $current_month_visitor_acquisition_all = $this->gauth->getResults($this->profile_id, 'current_month_visitor_acquisition_all', $this->current['start'], $this->current['end']);
        $previous_month_visitor_acquisition_all = $this->gauth->getResults($this->profile_id, 'previous_month_visitor_acquisition_all', $this->previous['start'], $this->previous['end']);

        if (!empty($visitor_acquisition_graph)) {
            $visitorGraph = array();
            $visitorTable = array();
            $visitorSum = array(
                '합계' => 0,
                '방문자' => 0,
                '방문경로' => ''
            );

            foreach ($visitor_acquisition_graph as $item) {
                $visitorGraph[$item[1]][$item[0]] = $item[2];
            }

            foreach ($current_month_visitor_acquisition_all as $item) {
                $visitorTable[$item[0]] = array(
                    '방문수' => $item[1],
                    '퇴장비율' => round($item[3], 2),
                    '매출' => $item[2],
                    '구매전환률' => round($item[4], 2),
                );

                $visitorSum['합계'] += $item[1];
                if ($visitorSum['방문자'] < $item[1]) {
                    $visitorSum['방문자'] = $item[1];
                    $visitorSum['방문경로'] = $item[0];
                }
            }

            $visitorSum['비율'] = round($visitorSum['방문자'] / $visitorSum['합계'] * 100, 2);

            foreach ($previous_month_visitor_acquisition_all as $item) {
                $visitorTable[$item[0]]['방문증감'] = $visitorTable[$item[0]]['방문수'] - $item[1];
                $visitorTable[$item[0]]['퇴장증감'] = $visitorTable[$item[0]]['퇴장비율'] - round($item[3], 2);
                $visitorTable[$item[0]]['매출증감'] = $visitorTable[$item[0]]['매출'] - $item[2];
                $visitorTable[$item[0]]['구매증감'] = $visitorTable[$item[0]]['구매전환률'] - round($item[4], 2);

            }

            $report = array(
                '방문추이그래프' => $visitorGraph,
                '방문통계' => $visitorTable,
                '요약' => $visitorSum
            );

            return $report;
        } else {
            return false;
        }
    }

    /**
     * 방문통계 검색
     * @return array|bool
     */
    public function visitStatSearch()
    {
        $visitor_acquisition_graph_organic = $this->gauth->getResults($this->profile_id, 'visitor_acquisition_graph_organic', $this->range7['start'], $this->current['end']);
        $current_month_visitor_acquisition_graph_organic = $this->gauth->getResults($this->profile_id, 'current_month_visitor_acquisition_graph_organic', $this->current['start'], $this->current['end']);
        $previous_month_visitor_acquisition_graph_organic = $this->gauth->getResults($this->profile_id, 'previous_month_visitor_acquisition_graph_organic', $this->previous['start'], $this->previous['end']);

        if (!empty($visitor_acquisition_graph_organic)) {
            $visitorGraph = array();
            $visitorTable = array();
            $visitorSum = array(
                '방문수' => 0,
                '검색엔진' => ''
            );

            foreach ($visitor_acquisition_graph_organic as $item) {
                $visitorGraph[$item[1]][$item[0]] = $item[2];
            }

            foreach ($current_month_visitor_acquisition_graph_organic as $item) {
                $visitorTable[$item[0]] = array(
                    '검색사이트' => $item[0],
                    '방문수' => $item[1],
                    '즉시퇴장비율' => round($item[3], 2),
                    '매출' => $item[2],
                    '구매전환율' => round($item[4], 2),
                    '방문수증감' => 0,
                    '즉시퇴장비율증감' => 0,
                    '매출증감' => 0,
                    '구매전환율증감' => 0
                );

                if ($visitorSum['방문수'] < $item[1]) {
                    $visitorSum['방문수'] = $item[1];
                    $visitorSum['검색엔진'] = $item[0];
                }
            }

            foreach ($previous_month_visitor_acquisition_graph_organic as $item) {
                if (isset($visitorTable[$item[0]])) {
                    $visitorTable[$item[0]]['방문수증감'] = $visitorTable[$item[0]]['방문수'] - $item[1];
                    $visitorTable[$item[0]]['즉시퇴장비율증감'] = $visitorTable[$item[0]]['즉시퇴장비율'] - round($item[3], 2);
                    $visitorTable[$item[0]]['매출증감'] = $visitorTable[$item[0]]['매출'] - $item[2];
                    $visitorTable[$item[0]]['구매전환율증감'] = $visitorTable[$item[0]]['구매전환율'] - round($item[4], 2);
                }
            }

            $report = array(
                '방문그래프' => $visitorGraph,
                '방문통계' => $visitorTable,
                '요약' => $visitorSum
            );

            return $report;
        } else {
            return false;
        }
    }

    /**
     * 방문통계 링크
     * @return array|bool
     */
    public function visitStatLink()
    {
        $visitor_acquisition_graph_referral = $this->gauth->getResults($this->profile_id, 'visitor_acquisition_graph_referral', $this->range6['start'], $this->current['end']);
        $current_month_visitor_acquisition_graph_referral = $this->gauth->getResults($this->profile_id, 'current_month_visitor_acquisition_graph_referral', $this->current['start'], $this->current['end']);
        $previous_month_visitor_acquisition_graph_referral = $this->gauth->getResults($this->profile_id, 'previous_month_visitor_acquisition_graph_referral', $this->previous['start'], $this->previous['end']);

        if (!empty($visitor_acquisition_graph_referral)) {
            $visitorGraph = array();
            $visitorTable = array();

            foreach ($visitor_acquisition_graph_referral as $item) {
                $visitorGraph[$item[1]][$item[0]] = $item[2];
            }

            foreach ($current_month_visitor_acquisition_graph_referral as $item) {
                $visitorTable[$item[0]] = array(
                    '링크 사이트' => $item[0],
                    '방문수' => $item[1],
                    '즉시퇴장비율' => round($item[3], 2),
                    '매출' => $item[2],
                    '구매전환율' => round($item[4], 2),
                    '방문수증감' => 0,
                    '즉시퇴장비율증감' => 0,
                    '매출증감' => 0,
                    '구매전환율증감' => 0
                );
            }


            foreach ($previous_month_visitor_acquisition_graph_referral as $item) {
                if (isset($visitorTable[$item[0]])) {
                    $visitorTable[$item[0]]['방문수증감'] = $visitorTable[$item[0]]['방문수'] - $item[1];
                    $visitorTable[$item[0]]['즉시퇴장비율증감'] = $visitorTable[$item[0]]['즉시퇴장비율'] - round($item[3], 2);
                    $visitorTable[$item[0]]['매출증감'] = $visitorTable[$item[0]]['매출'] - $item[2];
                    $visitorTable[$item[0]]['구매전환율증감'] = $visitorTable[$item[0]]['구매전환율'] - round($item[4], 2);
                }
            }

            $report = array(
                '방문그래프' => $visitorGraph,
                '방문통계' => $visitorTable,
            );

            return $report;
        } else {
            return false;
        }
    }

    /**
     * 방문통계 소셜
     * @return array|bool
     */
    public function visitStatSocial()
    {
        $visitor_acquisition_graph_social = $this->gauth->getResults($this->profile_id, 'visitor_acquisition_graph_social', $this->range7['start'], $this->current['end']);
        $current_month_visitor_acquisition_graph_social = $this->gauth->getResults($this->profile_id, 'current_month_visitor_acquisition_graph_social', $this->current['start'], $this->current['end']);
        $previous_month_visitor_acquisition_graph_social = $this->gauth->getResults($this->profile_id, 'previous_month_visitor_acquisition_graph_social', $this->previous['start'], $this->previous['end']);

        if (!empty($visitor_acquisition_graph_social)) {
            $visitorGraph = array();
            $visitorTable = array();
            $visitorSum = array(
                '방문수' => 0,
                '소셜네트워크' => ''
            );

            foreach ($visitor_acquisition_graph_social as $item) {
                $visitorGraph[$item[1]][$item[0]] = $item[2];
            }


            foreach ($current_month_visitor_acquisition_graph_social as $item) {
                $visitorTable[$item[0]] = array(
                    '소셜네트워크' => $item[0],
                    '방문수' => $item[1],
                    '즉시퇴장비율' => round($item[3], 2),
                    '매출' => $item[2],
                    '구매전환율' => round($item[4], 2),
                    '방문수증감' => 0,
                    '즉시퇴장비율증감' => 0,
                    '매출증감' => 0,
                    '구매전환율증감' => 0
                );

                if ($visitorSum['방문수'] < $item[1]) {
                    $visitorSum['방문수'] = $item[1];
                    $visitorSum['소셜네트워크'] = $item[0];
                }
            }

            foreach ($previous_month_visitor_acquisition_graph_social as $item) {
                if (isset($visitorTable[$item[0]])) {
                    $visitorTable[$item[0]]['방문수증감'] = $visitorTable[$item[0]]['방문수'] - $item[1];
                    $visitorTable[$item[0]]['즉시퇴장비율증감'] = $visitorTable[$item[0]]['즉시퇴장비율'] - round($item[3], 2);
                    $visitorTable[$item[0]]['매출증감'] = $visitorTable[$item[0]]['매출'] - $item[2];
                    $visitorTable[$item[0]]['구매전환율증감'] = $visitorTable[$item[0]]['구매전환율'] - round($item[4], 2);
                }
            }

            $report = array(
                '방문그래프' => $visitorGraph,
                '방문통계' => $visitorTable,
                '요약' => $visitorSum
            );

            return $report;
        } else {
            return false;
        }
    }

    public function visitorParticipation()
    {
        $average_revisited_trand = $this->gauth->getResults($this->profile_id, 'average_revisited_trand', $this->range6['start'], $this->current['end']);
        $average_residence_time_trend = $this->gauth->getResults($this->profile_id, 'average_residence_time_trend', $this->range6['start'], $this->current['end']);
        $pages_per_visit_trand = $this->gauth->getResults($this->profile_id, 'pages_per_visit_trand', $this->range6['start'], $this->current['end']);
        $average_bounce_rate_trend = $this->gauth->getResults($this->profile_id, 'average_bounce_rate_trend', $this->range6['start'], $this->current['end']);

        $report['재방문수'] = array();
        $report['평균체류시간'] = array();
        $report['방문당페이지수'] = array();
        $report['즉시이탈률'] = array();

        foreach ($average_revisited_trand as $item) {
            $report['재방문수'][$item[0]] = $item[1];
        }

        foreach ($average_residence_time_trend as $item) {
            $report['평균체류시간'][$item[0]] = round($item[1], 2);
        }

        foreach ($pages_per_visit_trand as $item) {
            $report['방문당페이지수'][$item[0]] = round($item[1], 2);
        }

        foreach ($average_bounce_rate_trend as $item) {
            $report['즉시이탈률'][$item[0]] = round($item[1], 2);
        }

        return $report;
    }

}