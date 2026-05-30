<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Video\ListVideosRequest;
use App\Http\Requests\Video\StoreVideoRequest;
use App\Http\Resources\VideoProcessingResource;
use App\Http\Resources\VideoTranscriptResource;
use App\Services\Video\VideoProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VideoController extends Controller
{
    public function __construct(
        private readonly VideoProcessingService $videoProcessingService,
    ) {}

    public function index(ListVideosRequest $request)
    {
        $data = $this->videoProcessingService->list($request->getDto());

        return VideoProcessingResource::collection($data);
    }

    public function store(StoreVideoRequest $request)
    {
        $data = $this->videoProcessingService->create($request->getDto());

        return (new VideoProcessingResource($data))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $uuid, Request $request)
    {
        $data = $this->videoProcessingService->getByUuid($uuid, $request->user());

        return new VideoProcessingResource($data);
    }

    public function transcript(string $uuid, Request $request)
    {
        $data = $this->videoProcessingService->getTranscriptByUuid($uuid, $request->user());

        return new VideoTranscriptResource($data);
    }

    public function srt(string $uuid, Request $request)
    {
        $video = $this->videoProcessingService->getByUuid($uuid, $request->user());

        if ($video->srtFile === null) {
            throw new NotFoundHttpException('SRT file is not ready yet.');
        }

        if (! Storage::disk($video->srtFile->disk)->exists($video->srtFile->path)) {
            throw new NotFoundHttpException('Stored SRT file is missing.');
        }

        $filename = pathinfo($video->originalName, PATHINFO_FILENAME);
        $filename = $filename !== '' ? $filename : $video->uuid;

        return response(Storage::disk($video->srtFile->disk)->get($video->srtFile->path), 200, [
            'Content-Type' => 'application/x-subrip; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s.srt"', $filename),
        ]);
    }

    public function retry(string $uuid, Request $request)
    {
        $data = $this->videoProcessingService->retryByUuid($uuid, $request->user());

        return (new VideoProcessingResource($data))->additional([
            'message' => 'Processing restarted',
        ]);
    }
}
