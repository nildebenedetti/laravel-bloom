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
        $spiderChart = DB::table('emotions')
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

            $pieChart = $pieChartBaseQuery
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

            // return response in json
            return response()->json([
                'time_range' => $timeRange,
                'charts' => [
                    'spider' => $spiderChart,
                    'pie' => $pieChart,

                ]
            ]);


    }
}

