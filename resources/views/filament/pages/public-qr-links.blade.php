<x-filament-panels::page>
  <div class="space-y-6">
    <x-filament::section>
      <x-slot name="heading">Pages accessibles par QR / lien direct</x-slot>
      <x-slot name="description">
        Générez et téléchargez les QR codes à imprimer ou à partager. L’inscription ouvrier n’apparaît pas dans le menu flottant du site.
      </x-slot>

      <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-2">
        @foreach ($links as $link)
          <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
              <div class="mx-auto shrink-0 rounded-xl bg-white p-2 ring-1 ring-gray-200 dark:ring-gray-700">
                <img
                  src="{{ $link['qrDataUri'] }}"
                  alt="QR {{ $link['label'] }}"
                  class="h-40 w-40"
                />
              </div>
              <div class="min-w-0 flex-1 space-y-2">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $link['label'] }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $link['description'] }}</p>
                <p class="break-all rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                  {{ $link['url'] }}
                </p>
                <div class="flex flex-wrap gap-2 pt-1">
                  <a
                    href="{{ $link['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-gray fi-size-sm gap-1 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/20"
                  >
                    Ouvrir
                  </a>
                  <a
                    href="{{ url('/admin/qr-download/'.$link['key']) }}"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-primary fi-btn-color-primary fi-size-sm gap-1 px-3 py-2 text-sm inline-grid shadow-sm text-white bg-primary-600 hover:bg-primary-500"
                  >
                    Télécharger PNG
                  </a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </x-filament::section>
  </div>
</x-filament-panels::page>
