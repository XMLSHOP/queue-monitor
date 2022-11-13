@if(null !== $summary)
    <div class="flex flex-wrap -mx-4 mb-2">
        <div class="w-full md:w-1/3 px-4 mb-4">
            <div class="h-full flex flex-col justify-between p-6 bg-white rounded shadow-md">
                <table class="text-m font-medium">
                    @foreach($summary as $status=>$amount)
                        @switch($status)
                            @case('pending')
                                <tr class="bg-yellow-100 text-yellow-800">
                                    <td>
                                        <div class="inline-flex flex-1 px-2">
                                            @lang('Pending')
                                        </div>
                                    </td>
                                    <td>
                                        <a class="text-underline"
                                           href="{{ Request::url() .'?queue='.$filters['queue'].'&job='.$filters['job'].'&type=pending' }}">{{ $amount }}</a>
                                    </td>
                                </tr>
                                @break
                            @case('running')
                                <tr class="bg-blue-100 text-blue-800">
                                    <td>
                                        <div class="inline-flex flex-1 px-2">
                                            @lang('Running')
                                        </div>
                                    </td>
                                    <td>
                                        <a class="text-underline"
                                           href="{{ Request::url() .'?queue='.$filters['queue'].'&job='.$filters['job'].'&type=&df='.$filters['df'].'&dt='.$filters['dt'] }}">{{ $amount }}</a>
                                    </td>
                                </tr>
                                @break
                            @case('succeeded')
                                <tr class="bg-green-200 text-green-800">
                                    <td>
                                        <div class="inline-flex flex-1 px-2">
                                            @lang('Success')
                                        </div>
                                    </td>
                                    <td>
                                        <a class="text-underline"
                                           href="{{ Request::url() .'?queue='.$filters['queue'].'&job='.$filters['job'].'&type=&df='.$filters['df'].'&dt='.$filters['dt'] }}">{{ $amount }}</a>
                                    </td>
                                </tr>
                                @break
                            @case('failed')
                                <tr class="bg-red-200 text-red-800">
                                    <td>
                                        <div class="inline-flex flex-1 px-2">
                                            @lang('Failed')
                                        </div>
                                    </td>
                                    <td>
                                        <a class="text-underline"
                                           href="{{ Request::url() .'?queue='.$filters['queue'].'&job='.$filters['job'].'&type=failed&df='.$filters['df'].'&dt='.$filters['dt'] }}">{{ $amount }}</a>
                                    </td>
                                </tr>
                                @break
                            @default
                                <tr>
                                    <td colspan="2">
                                        Exception!
                                    </td>
                                </tr>
                        @endswitch
                    @endforeach
                </table>
            </div>
        </div>

        @if($filters['job'] !== 'all' && $job_metrics && $filters['type'] !== 'failed')
            <div class="w-full md:w-1/3 px-4 mb-4">
                <div class="h-full flex flex-col justify-between p-6 bg-white rounded shadow-md">
                    <div class="font-semibold text-sm text-gray-600">Average Execution Time</div>
                    <div class="mt-0 text-xl">{{ round($job_metrics['execution_time'], 2) }}s</div>
                    <hr>
                    <div class="font-semibold text-sm text-gray-600">Average Pending Time</div>
                    <div class="mt-0 text-xl">
                        @if($job_metrics['pending_time'] > 3600)
                            {{ floor($job_metrics['pending_time'] / 3660) }}h
                            {{ floor(floor($job_metrics['pending_time'] - floor($job_metrics['pending_time'] / 3660) * 3660) / 60) }}m
                            {{ round($job_metrics['pending_time'] - floor($job_metrics['pending_time'] / 60) * 60, 2) }}s
                        @elseif($job_metrics['pending_time'] > 60)
                            {{ floor($job_metrics['pending_time'] / 60) }}m
                            {{ round($job_metrics['pending_time'] - floor($job_metrics['pending_time'] / 60) * 60, 2) }}s
                        @else
                            {{ round($job_metrics['pending_time'], 2) }}s
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
