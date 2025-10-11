<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppAuthController extends Controller
{
    /**
     * Get auth data by key
     */
    public function get($key)
    {
        try {
            $data = WhatsAppAuth::getAuth($key);
            
            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Auth data not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting WhatsApp auth data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving auth data'
            ], 500);
        }
    }

    /**
     * Store auth data
     */
    public function store(Request $request, $key)
    {
        try {
            $data = $request->input('data');
            
            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data provided'
                ], 400);
            }

            WhatsAppAuth::storeAuth($key, $data);

            Log::info('WhatsApp auth data saved', [
                'key' => $key,
                'data_size' => strlen(json_encode($data))
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Auth data saved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving WhatsApp auth data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving auth data'
            ], 500);
        }
    }

    /**
     * Delete auth data
     */
    public function delete($key)
    {
        try {
            $deleted = WhatsAppAuth::deleteAuth($key);

            Log::info('WhatsApp auth data deleted', [
                'key' => $key,
                'deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Auth data deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting WhatsApp auth data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting auth data'
            ], 500);
        }
    }

    /**
     * Clear all auth data
     */
    public function clear()
    {
        try {
            WhatsAppAuth::clearAll();

            Log::info('All WhatsApp auth data cleared');

            return response()->json([
                'success' => true,
                'message' => 'All auth data cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing WhatsApp auth data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error clearing auth data'
            ], 500);
        }
    }

    /**
     * Get auth data summary
     */
    public function summary()
    {
        try {
            $count = WhatsAppAuth::count();
            $keys = WhatsAppAuth::pluck('key_name');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_records' => $count,
                    'keys' => $keys
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting WhatsApp auth summary', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting auth summary'
            ], 500);
        }
    }
}