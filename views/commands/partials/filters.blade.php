<div class="px-6 py-4 mb-6 pl-4 bg-white rounded-md shadow-md">

    <h2 class="mb-4 text-2xl font-bold text-blue-900">
        @lang('Filter')
    </h2>

    <form action="" method="GET">
        <div class="flex items-center my-2 -mx-2">

            <div class="px-2 w-1/8">
                <label for="filter_show" class="block mb-1 text-xs uppercase font-semibold text-gray-600">
                    @lang('Status')
                </label>
                <select name="type" id="filter_show"
                        class="w-full p-2 bg-gray-200 border-2 border-gray-300 rounded">
                    <option @if($filters['type'] === 'all') selected @endif value="all">@lang('All')</option>
                    <option @if($filters['type'] === 'running') selected
                            @endif value="running">@lang('Running')</option>
                    <option @if($filters['type'] === 'failed') selected
                            @endif value="failed">@lang('Failed')</option>
                    <option @if($filters['type'] === 'succeeded') selected
                            @endif value="succeeded">@lang('Succeeded')</option>
                </select>
            </div>

            <div class="px-2 w-1/4">
                <label for="filter_commands" class="block mb-1 text-xs uppercase font-semibold text-gray-600">
                    @lang('Commands')
                </label>
                <select name="command" id="filter_commands"
                        class="w-full p-2 bg-gray-200 border-2 border-gray-300 rounded">
                    <option value="all">All</option>
                    @foreach($commands_list as $command)
                        <option @if((int)$filters['command'] == $command['id']) selected @endif value="{{ $command['id'] }}">
                            {{ $command['command'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="px-2 w-1/4">
                <div class="w-100">
                    <label for="filter_date_from"
                           class="mb-1 text-xs uppercase font-semibold text-gray-600"
                           style="width: 20% !important;">
                        @lang('From')&nbsp;
                        <span v-tooltip:bottom="'{{ __('Counts in: started, finished and failed.') }}'">
                            <i class="fa fa-info-circle"></i>
                        </span>
                    </label>
                    <input name="df" id="filter_date_from" type="datetime-local"
                           class="inline w-75 h-auto p-1 m-1 bg-gray-200 border-2 border-gray-300 rounded"
                           value="{{ $filters['df'] }}"
                    />
                </div>
                <div class="w-100">
                    <label for="filter_date_to"
                           class="w-25 mb-1 text-xs uppercase font-semibold text-gray-600"
                           style="width: 20% !important;">
                        @lang('To')&nbsp;
                        <span v-tooltip:bottom="'{{ __('Counts in: started, finished and failed.') }}'">
                            <i class="fa fa-info-circle"></i>
                        </span>
                    </label>
                    <input name="dt" id="filter_date_to" type="datetime-local"
                           class="inline w-75 h-auto p-1 m-1 bg-gray-200 border-2 border-gray-300 rounded"
                           value="{{ $filters['dt'] }}"
                    />
                </div>
            </div>
        </div>

        <div class="mt-4">

            <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-xs font-medium uppercase tracking-wider text-white rounded">
                @lang('Filter')
            </button>

        </div>

    </form>

</div>
