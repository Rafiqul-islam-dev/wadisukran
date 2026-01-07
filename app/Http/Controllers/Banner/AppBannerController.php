<?php

namespace App\Http\Controllers\Banner;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Str;

class AppBannerController extends Controller
{
    /**
     * Display a listing of the banners.
     */
    public function index()
    {
        // dd('ok');

        $banners = Banner::orderBy('created_at', 'desc')->get()->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $banner->image_url,
                'status' => $banner->status,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ];
        });


        return Inertia::render('Banner/Index', [
            'banners' => $banners,
        ]);
    }

    /**
     * Show the form for creating a new banner.
     */
    public function create()
    {
        return Inertia::render('Admin/Banners/Create');
    }

    /**
     * Store a newly created banner in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:banners,title',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            'status' => 'nullable|in:active,inactive',
        ], [
            'title.required' => 'Banner title is required.',
            'title.unique' => 'A banner with this title already exists.',
            'image.required' => 'Banner image is required.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Image must be a file of type: jpeg, png, jpg, gif, webp.',
            'image.max' => 'Image size should not exceed 10MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $banner = new Banner();
            $banner->title = $request->title;
            $banner->status = $request->status ?? 'active';

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('uploads/banners', $imageName);
                $banner->image_url = $imagePath;
            }

            $banner->save();

            return redirect()->route('banners.index')->with('success', 'Banner created successfully!');
        } catch (\Exception $e) {
            // If there was an error and an image was uploaded, delete it
            if (isset($imagePath) && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return redirect()->back()
                ->with('error', 'Failed to create banner. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified banner.
     */
    public function show(Banner $banner)
    {
        return Inertia::render('Admin/Banners/Show', [
            'banner' => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $banner->image_url ? Storage::url($banner->image_url) : null,
                'status' => $banner->status,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified banner.
     */
    public function edit(Banner $banner)
    {
        return Inertia::render('Admin/Banners/Edit', [
            'banner' => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $banner->image_url ? Storage::url($banner->image_url) : null,
                'status' => $banner->status,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ]
        ]);
    }

    /**
     * Update the specified banner in storage.
     */
    public function update(Request $request, Banner $banner)
    {
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('banners', 'title')->ignore($banner->id),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            'status' => 'nullable|in:active,inactive',
        ], [
            'title.required' => 'Banner title is required.',
            'title.unique' => 'A banner with this title already exists.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Image must be a file of type: jpeg, png, jpg, gif, webp.',
            'image.max' => 'Image size should not exceed 10MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $oldImagePath = $banner->image_url;

            $banner->title = $request->title;
            $banner->status = $request->status ?? $banner->status;

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('banners', $imageName, 'public');
                $banner->image_url = $imagePath;

                // Delete old image after successful upload
                if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            $banner->save();

            return redirect()->route('banners.index')->with('success', 'Banner updated successfully!');
        } catch (\Exception $e) {
            // If there was an error and a new image was uploaded, delete it
            if (isset($imagePath) && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return redirect()->back()
                ->with('error', 'Failed to update banner. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified banner from storage.
     */
    public function destroy(Banner $banner)
    {
        try {
            // Delete the image file if it exists
            if ($banner->image_url && Storage::disk('public')->exists($banner->image_url)) {
                Storage::disk('public')->delete($banner->image_url);
            }

            $banner->delete();

            return redirect()->route('banners.index')->with('success', 'Banner deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete banner. Please try again.');
        }
    }

    /**
     * Toggle banner status (active/inactive).
     */
    public function toggleStatus(Banner $banner)
    {
        try {
            $banner->status = $banner->status === 'active' ? 'inactive' : 'active';
            $banner->save();

            return redirect()->back()->with('success', 'Banner status updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update banner status. Please try again.');
        }
    }

    /**
     * Get active banners for frontend display.
     */
    public function getActiveBanners()
    {
        $banners = Banner::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'image_url' => $banner->image_url ? Storage::url($banner->image_url) : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    /**
     * Bulk delete banners.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner_ids' => 'required|array|min:1',
            'banner_ids.*' => 'required|integer|exists:banners,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with('error', 'Invalid banner selection.');
        }

        try {
            $banners = Banner::whereIn('id', $request->banner_ids)->get();

            foreach ($banners as $banner) {
                // Delete the image file if it exists
                if ($banner->image_url && Storage::disk('public')->exists($banner->image_url)) {
                    Storage::disk('public')->delete($banner->image_url);
                }
                $banner->delete();
            }

            $count = count($request->banner_ids);
            return redirect()->route('banners.index')
                ->with('success', "Successfully deleted {$count} banner(s)!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete selected banners. Please try again.');
        }
    }

    /**
     * Bulk update banner status.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner_ids' => 'required|array|min:1',
            'banner_ids.*' => 'required|integer|exists:banners,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with('error', 'Invalid request data.');
        }

        try {
            Banner::whereIn('id', $request->banner_ids)
                ->update(['status' => $request->status]);

            $count = count($request->banner_ids);
            $status = ucfirst($request->status);

            return redirect()->route('banners.index')
                ->with('success', "Successfully updated {$count} banner(s) to {$status}!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update banner status. Please try again.');
        }
    }

    /**
     * Search banners.
     */
    public function search(Request $request)
    {
        $query = $request->get('query', '');
        $status = $request->get('status', '');

        $banners = Banner::query()
            ->when($query, function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%");
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'image_url' => $banner->image_url ? Storage::url($banner->image_url) : null,
                    'status' => $banner->status,
                    'created_at' => $banner->created_at,
                    'updated_at' => $banner->updated_at,
                ];
            });

        return Inertia::render('Admin/Banners/Index', [
            'banners' => $banners,
            'filters' => [
                'query' => $query,
                'status' => $status,
            ],
        ]);
    }
}
