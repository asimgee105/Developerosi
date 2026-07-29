<?php

namespace App\Http\Controllers;

use App\Models\SpatialRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SpatialController extends Controller
{
    /**
     * Provision a WebXR spatial computing war room.
     */
    public function createRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'uuid'],
            'room_name' => ['required', 'string', 'max:255'],
        ]);

        $room = SpatialRoom::create([
            'workspace_id' => $validated['workspace_id'],
            'room_name' => $validated['room_name'],
            'layout_state' => [
                'floating_editor' => ['x' => 0, 'y' => 1.5, 'z' => -1.2, 'width' => 2, 'height' => 1.5],
                'floating_terminal' => ['x' => -1.5, 'y' => 1.2, 'z' => -1.0, 'width' => 1.2, 'height' => 1.0],
                'galaxy_ast_graph' => ['scale' => 1.0, 'nodes_count' => 124],
            ]
        ]);

        return response()->json([
            'message' => 'Spatial WebXR 3D collaborative room provisioned successfully.',
            'room' => $room,
            'webxr_connection' => [
                'websocket_url' => 'wss://spatial-gateway.devos.host/rooms/' . $room->id . '/sync',
                'webrtc_signaling_server' => 'https://rtc-signaling.devos.host/rooms/' . $room->id,
            ]
        ], 201);
    }
}
