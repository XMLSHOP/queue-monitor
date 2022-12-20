<h1 class="mb-6 text-5xl text-blue-900 font-bold">
    @lang('Monitor'): @lang('Commands')
</h1>

@include('monitor::commands.partials.filters', [
    'filters'=>$filters
])

<div class="overflow-x-auto shadow-lg">

    <table class="w-full rounded whitespace-no-wrap">

        <thead class="bg-gray-200">

        <tr>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Status')</th>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Command')</th>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Details')</th>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Duration')</th>
            <th class="px-4 py-3 font-medium text-left text-xs text-gray-600 uppercase border-b border-gray-200">@lang('Error')</th>
        </tr>

        </thead>

        <tbody class="bg-white">

        @forelse($commands as $command)

            <tr class="font-sm leading-relaxed">
                <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">
                    @if(!$command->isFinished())

                        <div class="inline-flex flex-1 px-2 text-xs font-medium leading-5 rounded-full bg-blue-200 text-blue-800">
                            @lang('Running')
                        </div>

                    @elseif($command->hasSucceeded())

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
                    {{ $command->command->command }}
                </td>

                <td class="p-4 text-gray-800 text-sm leading-5 font-medium border-b border-gray-200">
                    <div class="ml-1 text-xs text-gray-600">
                        <span class="text-gray-600 font-medium">Host:</span>
                        <span class="font-semibold">{{ $command->host->alias ?? $command->host->name }}</span>
                    </div>
                </td>

                <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">
                    <div class="ml-1 text-xs text-gray-1000">
                        Started: <strong>{{ $command->getStarted() }}</strong>
                        @if($command->getFinished())
                            <div>Finished: <strong>{{ $command->getFinished() }}</strong></div>
                            <div style="height: 5px"></div>
                            <div>Duration:
                                <strong>
                                    @if($command->getElapsedInterval()->format('%H:%I:%S') == '00:00:00')
                                        < 1s
                                    @else
                                        {{ $command->getElapsedInterval()->format('%H:%I:%S') }}
                                    @endif
                                </strong>
                            </div>
                        @endif
                    </div>
                </td>

                <td class="p-4 text-gray-800 text-sm leading-5 border-b border-gray-200">

                    @if($command->hasFailed() && $command->exception !== null)
                        <textarea rows="4" class="w-64 text-xs p-1 border rounded form-control" readonly
                                  style="resize: both"
                        >
                            {{ $command->exception->exception_message }}
                        </textarea>
                    @else
                        -
                    @endif

                </td>
            </tr>

        @empty
            <tr>
                <td colspan="100" class="">
                    <div class="my-6">
                        <div class="text-center">
                            <div class="text-gray-500 text-lg">
                                @lang('No Commands')
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
                        @if($commands->total() > 0)
                            <span class="font-medium">{{ $commands->firstItem() }}</span> @lang('to')
                            <span class="font-medium">{{ $commands->lastItem() }}</span> @lang('of')
                        @endif
                        <span class="font-medium">{{ $commands->total() }}</span> @lang('result')
                    </div>

                    <div>

                        <a class="py-2 px-4 mx-1 text-xs font-medium @if(!$commands->onFirstPage()) bg-gray-200 hover:bg-gray-300 cursor-pointer @else text-gray-600 bg-gray-100 cursor-not-allowed @endif rounded"
                           @if(!$commands->onFirstPage()) href="{{ $commands->previousPageUrl() }}" @endif>
                            @lang('Previous')
                        </a>

                        <a class="py-2 px-4 mx-1 text-xs font-medium @if($commands->hasMorePages()) bg-gray-200 hover:bg-gray-300 cursor-pointer @else text-gray-600 bg-gray-100 cursor-not-allowed @endif rounded"
                           @if($commands->hasMorePages()) href="{{ $commands->url($commands->currentPage() + 1) }}" @endif>
                            @lang('Next')
                        </a>

                    </div>

                </div>

            </td>

        </tr>

        </tfoot>

    </table>

</div>
