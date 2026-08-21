@php
    use App\Filament\Resources\GuestInfoSubmissionResource;
    use App\Models\GuestInfoSubmission;

    /** @var GuestInfoSubmission|null $record */
    $record = $record ?? (isset($getRecord) ? $getRecord() : null);
    $answers = $answers ?? ($record instanceof GuestInfoSubmission
        ? GuestInfoSubmissionResource::answersForDisplay($record)
        : []);
    $pastorName = $record instanceof GuestInfoSubmission
        ? ($record->guestPastor?->full_name ?? 'Pasteur')
        : 'Pasteur';
@endphp

@if($record instanceof GuestInfoSubmission)
  <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">
    <p class="font-semibold text-gray-900 dark:text-white">{{ $pastorName }}</p>
    <p class="mt-1 text-gray-600 dark:text-gray-300">
      Formulaire : {{ $record->form?->title ?? '—' }}
      @if($record->submitted_at)
        · Reçu le {{ $record->submitted_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
      @endif
    </p>
  </div>
@endif

@include('filament.guest-forms.submission-answers', ['answers' => $answers, 'getRecord' => fn () => $record])
