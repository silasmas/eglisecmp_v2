<div
  x-data="{ viewport: 'desktop' }"
  class="space-y-4"
>
  <div class="flex flex-wrap items-center gap-2">
    <button
      type="button"
      @click="viewport = 'desktop'"
      :class="viewport === 'desktop' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200'"
      class="rounded-lg px-3 py-1.5 text-sm font-medium"
    >
      PC
    </button>
    <button
      type="button"
      @click="viewport = 'mobile'"
      :class="viewport === 'mobile' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200'"
      class="rounded-lg px-3 py-1.5 text-sm font-medium"
    >
      Mobile
    </button>
    <span class="text-xs text-gray-500 dark:text-gray-400">
      Mode : {{ ($form->layout_mode ?? 'single') === 'wizard' ? 'Assistant (étapes)' : 'Page unique' }}
      · {{ $form->sections->count() }} rubrique(s)
    </span>
  </div>

  <div class="flex justify-center rounded-xl bg-gray-100 p-4 dark:bg-gray-900">
    <div
      class="overflow-hidden bg-white shadow-xl transition-all dark:bg-gray-950"
      :style="viewport === 'mobile' ? 'width: 390px; max-width: 100%; border-radius: 24px;' : 'width: 100%; max-width: 720px; border-radius: 16px;'"
      style="--guest-primary: {{ $form->design['primary_color'] ?? '#7b1d3e' }}; --guest-accent: {{ $form->design['accent_color'] ?? '#ea7e2d' }}; --guest-radius: {{ (int) ($form->design['radius'] ?? 16) }}px;"
    >
      @php
        $banner = $form->design['banner_path'] ?? null;
        if (is_string($banner) && $banner !== '' && ! str_starts_with($banner, 'http')) {
          $banner = \Illuminate\Support\Facades\Storage::disk('public')->url($banner);
        }
      @endphp

      @if($banner)
        <div class="h-28 w-full bg-cover bg-center sm:h-36" style="background-image: url('{{ $banner }}')"></div>
      @else
        <div class="h-20 w-full" style="background: linear-gradient(135deg, var(--guest-primary), var(--guest-accent))"></div>
      @endif

      <div class="space-y-4 p-5">
        <div class="flex items-start gap-3">
          @if(! empty($pastorPhotoUrl))
            <img src="{{ $pastorPhotoUrl }}" alt="" class="h-14 w-14 rounded-full object-cover ring-2 ring-orange-300">
          @endif
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--guest-accent)">
              {{ $form->project?->title ?? 'Accueil CMP' }}
            </p>
            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $headline }}</h3>
          </div>
        </div>

        @if(filled($form->intro_html))
          <div class="prose prose-sm max-w-none text-gray-700 dark:prose-invert">{!! $form->intro_html !!}</div>
        @endif

        @if(($form->layout_mode ?? '') === 'wizard')
          <div class="space-y-1">
            <div class="flex justify-between text-[11px] text-gray-500">
              <span>Étape 1 / {{ max(1, $form->sections->count()) }}</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
              <div class="h-full w-1/{{ max(1, $form->sections->count()) }}" style="background: var(--guest-accent); width: {{ $form->sections->count() > 0 ? round(100 / $form->sections->count()) : 100 }}%"></div>
            </div>
          </div>
        @endif

        @foreach($form->sections as $index => $section)
          @if(($form->layout_mode ?? '') === 'wizard' && $index > 0)
            @continue
          @endif
          <section class="space-y-3 border-t border-gray-200 pt-4 dark:border-gray-800">
            <div>
              <h4 class="font-semibold text-gray-900 dark:text-white">{{ $section->title }}</h4>
              @if(filled($section->description))
                <p class="text-xs text-gray-500">{{ $section->description }}</p>
              @endif
            </div>
            @foreach($section->fields as $field)
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                  {{ $field->label }}@if($field->required)<span class="text-red-500"> *</span>@endif
                </label>
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-900">
                  {{ \App\Models\GuestInfoFormField::typeOptions()[$field->type] ?? $field->type }}
                </div>
              </div>
            @endforeach
          </section>
        @endforeach

        @if(filled($form->cmp_info_html) && ($form->layout_mode ?? '') !== 'wizard')
          <aside class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-1 font-semibold">Infos CMP</p>
            <div class="prose prose-xs max-w-none dark:prose-invert">{!! $form->cmp_info_html !!}</div>
          </aside>
        @endif

        <div class="pt-2">
          <span
            class="inline-flex rounded-xl px-4 py-2 text-xs font-semibold text-white"
            style="background: var(--guest-primary)"
          >
            {{ ($form->layout_mode ?? '') === 'wizard' ? 'Suivant →' : 'Envoyer la fiche' }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <p class="text-center text-xs text-gray-500 dark:text-gray-400">
    Aperçu basé sur les données enregistrées. Enregistrez le formulaire avant d’ouvrir l’aperçu pour voir les derniers changements.
  </p>
</div>
