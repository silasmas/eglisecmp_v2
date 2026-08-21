@php
    use App\Filament\Resources\GuestInfoSubmissionResource;
    use App\Models\GuestInfoSubmission;

    $record = $getRecord();
    $answers = $record instanceof GuestInfoSubmission
        ? GuestInfoSubmissionResource::answersForDisplay($record)
        : [];
@endphp

@if($answers === [])
  <p class="text-sm text-gray-500 dark:text-gray-400">Aucune réponse visible pour votre périmètre.</p>
@else
  <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
    <dl class="divide-y divide-gray-200 dark:divide-gray-700">
      @foreach($answers as $answer)
        <div class="grid gap-1 bg-white px-4 py-3 sm:grid-cols-12 sm:gap-4 dark:bg-gray-900">
          <dt class="text-sm font-semibold text-gray-700 sm:col-span-4 dark:text-gray-200">
            {{ $answer['label'] }}
          </dt>
          <dd class="whitespace-pre-wrap text-sm text-gray-900 sm:col-span-8 dark:text-gray-100">
            {{ $answer['value'] }}
          </dd>
        </div>
      @endforeach
    </dl>
  </div>
@endif
