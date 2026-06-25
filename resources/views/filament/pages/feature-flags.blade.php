<x-filament-panels::page>
    <div class="grid gap-6">
        @foreach ($this->featureRows() as $feature)
            <x-filament::section>
                <x-slot name="heading">
                    {{ $feature['title'] }}
                </x-slot>

                <x-slot name="description">
                    {{ $feature['description'] }}
                </x-slot>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">Status</p>
                        <x-filament::badge :color="$feature['statusColor']" class="mt-2">
                            {{ $feature['status'] }}
                        </x-filament::badge>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">Pennant key</p>
                        <p class="mt-2 font-mono text-sm text-gray-600 dark:text-gray-300">{{ $feature['key'] }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">Config default</p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $feature['configDefault'] }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">Pennant store</p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $feature['store'] }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">Scope</p>
                        <p class="mt-2 font-mono text-sm text-gray-600 dark:text-gray-300">{{ $feature['scope'] }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">Endpoint</p>
                        <p class="mt-2 break-all font-mono text-sm text-gray-600 dark:text-gray-300">{{ $feature['endpoint'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
