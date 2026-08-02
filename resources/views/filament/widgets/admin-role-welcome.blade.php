<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-gray-950 dark:text-white">
                    Bonjour, {{ $name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Tableau de bord adapté à vos rôles et permissions.
                </p>
                @if (count($roles) > 0)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($roles as $role)
                            <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30">
                                {{ $role }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500">
                Visite guidée : icône 🎓 en haut à droite
            </p>
        </div>

        @if (count($highlights) > 0)
            <ul class="mt-4 grid gap-2 text-sm text-gray-700 dark:text-gray-200 sm:grid-cols-2">
                @foreach ($highlights as $highlight)
                    <li class="flex items-start gap-2 rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                        <x-filament::icon icon="heroicon-m-check-circle" class="mt-0.5 h-4 w-4 text-primary-600 dark:text-primary-400" />
                        <span>{{ $highlight }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
