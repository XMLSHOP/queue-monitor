<h1 class="mb-6 text-5xl text-blue-900 font-bold">
    @lang('Queue Monitor')
</h1>

@isset($metrics)

    <div class="flex flex-wrap -mx-4 mb-2">

        @foreach($metrics->all() as $metric)

            @include('queue-monitor::partials.metrics-card', [
                'metric' => $metric,
            ])

        @endforeach

    </div>

@endisset

@include('queue-monitor::partials.filters', [
    'filters'=>$filters
])

@include('queue-monitor::partials.summary-card', [
    'filters'=>$filters
])

<div class="overflow-x-auto shadow-lg">

    <table class="w-full rounded whitespace-no-wrap">

        <thead class="bg-gray-200">

        <tr>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Status')</th>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Job')</th>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Details')</th>

            @if(config('queue-monitor.ui.show_custom_data'))
                <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Custom Data')</th>
            @endif

            @if(config('queue-monitor.ui.show_progress_column'))
                <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Progress')</th>
            @endif

            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Duration')</th>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Queued')</th>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Started')</th>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Error')</th>

            @if(config('queue-monitor.ui.allow_deletion'))
                <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Action')</th>
            @endif
        </tr>

        </thead>

        <tbody class="bg-white">

        @forelse($jobs as $job)

            <tr class="font-sm leading-relaxed">
                <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">
                    @if($job->isPending())
                        <div class="inline-flex flex-1 px-2 text-xs font-medium leading-5 rounded-full bg-yellow-200 text-yellow-800">
                            @lang('Pending')
                        </div>
                    @elseif(!$job->isFinished())

                        <div class="inline-flex flex-1 px-2 text-xs font-medium leading-5 rounded-full bg-blue-200 text-blue-800">
                            @lang('Running')
                        </div>

                    @elseif($job->hasSucceeded())

                        <div class="inline-flex flex-1 px-2 text-xs font-medium leading-5 rounded-full bg-green-200 text-green-800">
                            @lang('Success')
                        </div>

                    @else

                        <div class="inline-flex flex-1 px-2 text-xs font-medium leading-5 rounded-full bg-red-200 text-red-800">
                            @lang('Failed')
                        </div>

                    @endif

                </td>

                <td class="p-4 text-gray-800 text-sm leading-5 font-medium border-b border-gray-200">

                    {{ \xmlshop\QueueMonitor\Models\QueueMonitorJobModel::getBaseName($job->name) }}

                    <div class="ml-1 text-xs text-gray-600">
                        #{{ $job->job_id }}
                    </div>

                </td>

                <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">

                    <div class="text-xs">
                        <span class="text-gray-600 font-medium">@lang('Queue'):</span>
                        @if(preg_match("~https\:\/\/sqs\.(?<region>[\w-]+)\.\w+\.com\/(?<id>\w+)\/(?<name>\w+)~", $job->queue, $matches))
                            <span class="font-semibold"><span
                                        class="font-medium">SQS</span> {{ $matches['name'] }}</span>
                            <br/>
                            <span class="font-semibold">{{ $matches['region'] }} :: {{ $matches['id'] }}</span>
                        @else
                            <span class="font-semibold">{{ $job->queue }}</span>
                        @endif
                    </div>

                    <div class="text-xs">
                        <span class="text-gray-600 font-medium">@lang('Attempt'):</span>
                        <span class="font-semibold">{{ $job->attempt }}</span>
                    </div>

                </td>

                @if(config('queue-monitor.ui.show_custom_data'))

                    <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">

                                    <textarea rows="4"
                                              class="w-64 text-xs p-1 border rounded form-control"
                                              readonly style="resize: both">{{ json_encode($job->getData(), JSON_PRETTY_PRINT) }}
                                    </textarea>

                    </td>

                @endif

                @if(config('queue-monitor.ui.show_progress_column'))
                    <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">

                        @if($job->progress !== null)

                            <div class="w-32">

                                <div class="flex items-stretch h-3 rounded-full bg-gray-300 overflow-hidden">
                                    <div class="h-full bg-green-500" style="width: {{ $job->progress }}%"></div>
                                </div>

                                <div class="flex justify-center mt-1 text-xs text-gray-800 font-semibold">
                                    {{ $job->progress }}%
                                </div>

                            </div>

                        @else
                            -
                        @endif

                    </td>
                @endif

                <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">
                    {{ $job->getElapsedInterval()->format('%H:%I:%S') }}
                </td>

                <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">
                    @if(null !== $job->queued_at)
                        {{ $job->queued_at->diffForHumans() }}
                    @else
                        -
                    @endif
                </td>

                <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">
                    @if(null !== $job->started_at)
                        {{ $job->started_at->diffForHumans() }}
                    @else
                        -
                    @endif
                </td>

                <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">

                    @if($job->hasFailed() && $job->exception_message !== null)

                        <textarea rows="4" class="w-64 text-xs p-1 border rounded form-control" readonly
                                  style="resize: both"
                        >{{ $job->exception_message }}</textarea>

                    @else
                        -
                    @endif

                </td>

                @if(config('queue-monitor.ui.allow_deletion'))

                    <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">

                        <form action="{{ route('queue-monitor::destroy', [$job]) }}" method="post">

                            @csrf
                            @method('delete')

                            <button class="px-3 py-1 bg-red-200 hover:bg-red-300 text-red-800 text-xs font-medium uppercase tracking-wider text-white rounded">
                                @lang('Delete')
                            </button>

                        </form>

                    </td>

                @endif

            </tr>

        @empty

            <tr>

                <td colspan="100" class="">

                    <div class="my-6">

                        <div class="text-center">

                            <div class="text-gray-500 text-lg">
                                @lang('No Jobs')
                            </div>

                        </div>

                    </div>

                </td>

            </tr>

        @endforelse

        </tbody>

        <tfoot class="bg-white">

        <tr>

            <td colspan="100" class="px-6 py-4 text-gray-700 font-sm border-t-2 border-gray-200">

                <div class="flex justify-between">

                    <div>
                        @lang('Showing')
                        @if($jobs->total() > 0)
                            <span class="font-medium">{{ $jobs->firstItem() }}</span> @lang('to')
                            <span class="font-medium">{{ $jobs->lastItem() }}</span> @lang('of')
                        @endif
                        <span class="font-medium">{{ $jobs->total() }}</span> @lang('result')
                    </div>

                    <div>

                        <a class="py-2 px-4 mx-1 text-xs font-medium @if(!$jobs->onFirstPage()) bg-gray-200 hover:bg-gray-300 cursor-pointer @else text-gray-600 bg-gray-100 cursor-not-allowed @endif rounded"
                           @if(!$jobs->onFirstPage()) href="{{ $jobs->previousPageUrl() }}" @endif>
                            @lang('Previous')
                        </a>

                        <a class="py-2 px-4 mx-1 text-xs font-medium @if($jobs->hasMorePages()) bg-gray-200 hover:bg-gray-300 cursor-pointer @else text-gray-600 bg-gray-100 cursor-not-allowed @endif rounded"
                           @if($jobs->hasMorePages()) href="{{ $jobs->url($jobs->currentPage() + 1) }}" @endif>
                            @lang('Next')
                        </a>

                    </div>

                </div>

            </td>

        </tr>

        </tfoot>

    </table>

</div>

@if(config('queue-monitor.ui.allow_purge'))

    <div class="mt-12">

        <form action="{{ route('queue-monitor::purge') }}" method="post">

            @csrf
            @method('delete')

            <button class="px-3 py-1 bg-red-200 hover:bg-red-300 text-red-800 text-xs font-medium uppercase tracking-wider text-white rounded">
                @lang('Delete all entries')
            </button>

        </form>

    </div>

@endif
