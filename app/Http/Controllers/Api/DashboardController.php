<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    
    public function stats(Request $request) {
        // get user
        $user = $request->user();
        // parameter MUST be sent by frontend in request params
        $timeRange = $request->input('time_range', 'all_time');

        // base query: get all_time
        $baseQuery = $user->records();

        // HANDLE PERIOD FILTERING
        // shall any value be selected, we filter the collection
        if ($timeRange === 'last_month') {
            $baseQuery->where('date', '>=', now()->subMonth()); // now - exactly 30 days referred to current date/time
        } elseif ($timeRange === 'last_six_months') {
            $baseQuery->where('date', '>=', now()->subMonths(6));
        }

        // NOW, THE DYNAMIC CHARTS

        // spider: emotion distribution in records, how many time they 
        // appeared in the records
        $spiderChartData = DB::table('emotions')
            // join emotion_record table 
            // ON 'emotion.id' = 'emotion_record.emotion_id'
            ->join('emotion_record', 'emotions.id', '=', 'emotion_record.emotion_id')
            // join record table
            // ON 'record.id' = 'emotion_record.record_id'
            ->join('records', 'records.id', '=', 'emotion_record.record_id')
            // take records of logged user
            ->where('records.user_id', '=', $user->id)
            // if timeRange is set as last_month, the callback function
            // activates and adds its query segment to the build
            ->when($timeRange === 'last_month', function ($q) {
                $q->where('records.date', '>=', now()->subMonth());
            })
            // same logic yet activates when value is set to last_six_months
            ->when($timeRange === 'last_six_months', function ($q) {
                $q->where('records.date', '>=', now()->subMonths(6));
            })
            // select emotions and record count
            ->select('emotions.name as emotion', DB::raw('count(records.id) as count'))
            ->groupBy('emotions.id', 'emotions.name')
            ->get();


            // pie chart
            // records distribution by category
            $pieChartBaseQuery = clone $baseQuery; // clone as otherwise the reference is copied, not the value and we would infer destructively the first variable by manipulating the latter

            $pieChartData = $pieChartBaseQuery
            ->join('categories', 'categories.id', '=', 'records.category_id')
            // for each category, provide the count
            ->select('categories.name as category', DB::raw('count(*) as count'))
            // then group raws
            ->groupBy('category')
            ->get();

            // what happens in SQL:
            // SELECT categories.name as category, count(*) as count 
            // FROM records 
            // INNER JOIN categories ON categories.id = records.category_id
            // WHERE records.user_id = N AND records.date >= '2026-XX-23' -- (filters from $baseQuery)
            // GROUP BY category;

            // Area Chart velocity vs. payload
            // extract raw data with query
            $areaChartBaseQuery = clone $baseQuery;

            $rawTImeline = $baseQuery // user records with dynamic time period
                ->join('tiers', 'tiers.id', '=', 'records.tier_id')
                ->select(
                    DB::raw("DATE_FORMAT(date, '%Y-%m') as month"), // extract date, convert to yer-month, rename as month
                    'tiers.id as tier',
                    DB::raw('count(*) as count')
                )
                ->groupBy('month', 'tier')
                ->orderBy('month', 'asc')
                ->get();


            // what happens in SQL:
            // SELECT DATE_FORMAT(date, '%Y-%m') as month, tier, count(*) as count
            // FROM records
            // WHERE user_id = 1 AND date >= '2026-03-23' -- (inherited from $baseQuery)
            // GROUP BY month, tier
            // ORDER BY month ASC;

            // transofrm data ready to serve for chart format

            // create empty array for month groups!
            $timelineGrouped = [];

            foreach($rawTImeline as $row) {
                $month = $row->month;
                $tierKey = 'tier_' . $row->tier;

                // for each first mention of a month, a group is created as a value in the array
                // in the group, is created a key month with corresponding value

                if(!isset($timelineGrouped[$month])) {
                    $timelineGrouped[$month] = [ 'month' => $month ];
                }

                // this is executed for each row, regardless if a new group is created or not
                // it creates or updates a dynamic key for tierKey
                //
                // In PHP, an associative array is a key => value map.
                // Using two levels of brackets targets and updates only that specific sub-key,
                // leaving the rest of the array's existing keys (like 'month') intact.

                $timelineGrouped[$month][$tierKey] = (int) $row->count;

            }

            // array_values() removes the string keys ('2026-07', '2026-08') while preserving
            // the exact insertion order (guaranteed by SQL's ORDER BY), re-indexing the array
            // into a sequential numeric list (0, 1, 2...) required by Recharts.
            $areaChartData = array_values($timelineGrouped);
            // $areaChartData = $timelineGrouped;

            // return response in json
            return response()->json([
                'time_range' => $timeRange,
                'charts' => [
                    'spider' => $spiderChartData,
                    'pie'    => $pieChartData,
                    'area'   => $areaChartData
                ]
            ]);


    }
}

