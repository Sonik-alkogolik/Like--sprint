<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdvertiserTaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks)
    {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tasks = Task::query()
            ->where('advertiser_id', $user->id)
            ->with(['requirements', 'links'])
            ->orderByDesc('id')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $task = $this->tasks->create($user, $validator->validated());

        return response()->json(['task' => $task], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ((int) $task->advertiser_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), $this->rules(false));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $task = $this->tasks->update($task, $validator->validated());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['task' => $task]);
    }

    public function submitModeration(Request $request, Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ((int) $task->advertiser_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $task = $this->tasks->submitToModeration($task);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['task' => $task]);
    }

    public function launch(Request $request, Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ((int) $task->advertiser_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $task = $this->tasks->launch($user, $task);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['task' => $task]);
    }

    public function pause(Request $request, Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ((int) $task->advertiser_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $task = $this->tasks->pause($task);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['task' => $task]);
    }

    private function rules(bool $create = true): array
    {
        $required = $create ? ['required'] : ['sometimes'];

        return [
            'title' => array_merge($required, ['string', 'max:140']),
            'short_description' => ['sometimes', 'nullable', 'string'],
            'instruction' => array_merge($required, ['string', 'max:10000']),
            'start_url' => ['sometimes', 'nullable', 'url', 'max:1000'],
            'price_per_action' => array_merge($required, ['numeric', 'min:0.01']),
            'commission_per_action' => ['sometimes', 'numeric', 'min:0'],
            'max_approvals' => array_merge($required, ['integer', 'min:1', 'max:500000']),
            'repeat_mode' => array_merge($required, [Rule::in(['one_time', 'repeat_after_review', 'repeat_interval'])]),
            'repeat_interval_hours' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:240'],
            'assignment_ttl_minutes' => ['sometimes', 'integer', 'min:5', 'max:43200'],
            'check_deadline_days' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'verification_mode' => ['sometimes', Rule::in(['manual', 'auto_accept'])],
            'requirements' => ['sometimes', 'array'],
            'requirements.*.kind' => ['sometimes', Rule::in(['text', 'link', 'screenshot', 'file'])],
            'requirements.*.label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'requirements.*.is_required' => ['sometimes', 'boolean'],
            'links' => ['sometimes', 'array'],
            'links.*.url' => ['sometimes', 'url', 'max:1000'],
            'links.*.label' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}