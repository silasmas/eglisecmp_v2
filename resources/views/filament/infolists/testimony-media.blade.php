@php
  use App\Models\Testimony;
  use App\Support\SitePublicSerializer;
  use Illuminate\Support\Facades\Storage;

  /** @var Testimony $record */
  $record = $getRecord();
  $record->loadMissing('images');
  $embedUrl = SitePublicSerializer::youtubeEmbedUrlFromLink($record->video);
  $videoFileUrl = filled($record->video_file)
      ? Storage::disk('public')->url($record->video_file)
      : null;
@endphp

<div class="space-y-4">
  @if ($embedUrl !== '')
    <div>
      <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Vidéo YouTube</p>
      <div class="aspect-video max-w-2xl overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
        <iframe src="{{ $embedUrl }}" class="h-full w-full" allowfullscreen title="Aperçu vidéo"></iframe>
      </div>
    </div>
  @endif

  @if ($videoFileUrl)
    <div>
      <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Vidéo uploadée</p>
      <video src="{{ $videoFileUrl }}" controls class="max-h-80 max-w-2xl rounded-xl border border-gray-200"></video>
    </div>
  @endif

  @if ($record->images->isNotEmpty())
    <div>
      <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Photos</p>
      <div class="flex flex-wrap gap-3">
        @foreach ($record->images as $image)
          <a
            href="{{ Storage::disk('public')->url($image->image) }}"
            target="_blank"
            rel="noopener"
            class="block shrink-0 overflow-hidden rounded-lg border border-gray-200 shadow-sm transition hover:ring-2 hover:ring-primary-500"
          >
            <img
              src="{{ Storage::disk('public')->url($image->image) }}"
              alt=""
              class="h-28 w-40 object-cover"
            />
          </a>
        @endforeach
      </div>
    </div>
  @endif
</div>
