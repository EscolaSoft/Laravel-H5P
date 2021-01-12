<?php

namespace EscolaSoft\LaravelH5p\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use EscolaSoft\LaravelH5p\Eloquents\H5pContent;
use EscolaSoft\LaravelH5p\Eloquents\H5pLibrary;
use H5PCore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Log;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $interface = $h5p::$interface;
        $not_cached = $interface->getNumNotFiltered();

        $entrys = H5pLibrary::paginate(10);
        $settings = $h5p::get_core([
            'libraryList' => [
                'notCached' => $not_cached,
            ],
            'containerSelector' => '#h5p-admin-container',
            'extraTableClasses' => '',
            'l10n'              => [
                'NA'             => trans('laravel-h5p.common.na'),
                'viewLibrary'    => trans('laravel-h5p.library.viewLibrary'),
                'deleteLibrary'  => trans('laravel-h5p.library.deleteLibrary'),
                'upgradeLibrary' => trans('laravel-h5p.library.upgradeLibrary'),
            ],
        ]);

        foreach ($entrys as $library) {
            $usage = $interface->getLibraryUsage($library->id, $not_cached ? true : false);
            $settings['libraryList']['listData'][] = (object) [
                'id'                     => $library->id,
                'title'                  => $library->title.' ('.H5PCore::libraryVersion($library).')',
                'restricted'             => ($library->restricted ? true : false),
                'numContent'             => $interface->getNumContent($library->id),
                'numContentDependencies' => intval($usage['content']),
                'numLibraryDependencies' => intval($usage['libraries']),
            ];
        }

        $last_update = config('laravel-h5p.h5p_content_type_cache_updated_at');

        $required_files = $this->assets(['js/h5p-library-list.js']);

        if ($not_cached) {
            $settings['libraryList']['notCached'] = $this->get_not_cached_settings($not_cached);
        } else {
            $settings['libraryList']['notCached'] = 0;
        }
        $hubOn = config('laravel-h5p.h5p_hub_is_enabled');
        return view('h5p.library.index', compact('entrys', 'settings', 'last_update', 'hubOn', 'required_files'));
    }

    public function show(Request $request, $id)
    {
        $library = $this->get_library($id);

        // Add settings and translations
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $interface = $h5p::$interface;

        $settings = [
            'containerSelector' => '#h5p-admin-container',
        ];

        // Build the translations needed
        $settings['libraryInfo']['translations'] = [
            'noContent'             => trans('laravel-h5p.library.noContent'),
            'contentHeader'         => trans('laravel-h5p.library.contentHeader'),
            'pageSizeSelectorLabel' => trans('laravel-h5p.library.pageSizeSelectorLabel'),
            'filterPlaceholder'     => trans('laravel-h5p.library.filterPlaceholder'),
            'pageXOfY'              => trans('laravel-h5p.library.pageXOfY'),
        ];
        $notCached = $interface->getNumNotFiltered();
        if ($notCached) {
            $settings['libraryInfo']['notCached'] = $this->get_not_cached_settings($notCached);
        } else {
            // List content which uses this library
            $contents = DB::select('SELECT DISTINCT hc.id, hc.title FROM h5p_contents_libraries hcl JOIN h5p_contents hc ON hcl.content_id = hc.id WHERE hcl.library_id = ? ORDER BY hc.title', [$library->id]);

            foreach ($contents as $content) {
                $settings['libraryInfo']['content'][] = [
                    'title' => $content->title,
                    'url'   => route('h5p.show', ['id' => $content->id]),
                ];
            }
        }
        // Build library info
        $settings['libraryInfo']['info'] = [
            'version'         => H5PCore::libraryVersion($library),
            'fullscreen'      => $library->fullscreen ? trans('laravel-h5p.common.yes') : trans('laravel-h5p.common.no'),
            'content_library' => $library->runnable ? trans('laravel-h5p.common.yes') : trans('laravel-h5p.common.no'),
            'used'            => (isset($contents) ? count($contents) : trans('laravel-h5p.common.na')),
        ];

        $required_files = $this->assets(['js/h5p-library-details.js']);

        return view('h5p.library.show', compact('settings', 'required_files', 'library'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'h5p_file' => 'required||max:50000',
        ]);

        if ($request->hasFile('h5p_file') && $request->file('h5p_file')->isValid()) {
            Log::info('Yes Good ');
            $h5p = App::make('LaravelH5p');
            $validator = $h5p::$validator;
            $interface = $h5p::$interface;

            // Content update is skipped because it is new registration
            $content = null;
            $skipContent = true;
            $h5p_upgrade_only = ($request->get('h5p_upgrade_only')) ? true : false;

            rename($request->file('h5p_file')->getPathName(), $interface->getUploadedH5pPath());

            if ($validator->isValidPackage($skipContent, $h5p_upgrade_only)) {
                $storage = $h5p::$storage;
                $storage->savePackage($content, null, $skipContent);
                Log::info('All is OK ');
            }

//            if ($request->get('sync_hub')) {
            //                $h5p::$core->updateContentTypeCache();
            //            }
            // The uploaded file was not a valid H5P package
            @unlink($interface->getUploadedH5pPath());

            return redirect()
                ->route('h5p.library.index')
                ->with('success', trans('laravel-h5p.library.updated'));
        }

        Log::info('Not Good Good ');

        return redirect()
            ->route('h5p.library.index')
            ->with('error', trans('laravel-h5p.library.can_not_updated'));
    }

    public function destroy(Request $request)
    {
        $library = H5pLibrary::findOrFail($request->get('id'));

        $h5p = App::make('LaravelH5p');
        $interface = $h5p::$interface;

        // Error if in use
        $usage = $interface->getLibraryUsage($library);
        if ($usage['content'] !== 0 || $usage['libraries'] !== 0) {
            return redirect()->route('h5p.library.index')
                ->with('error', trans('laravel-h5p.library.used_library_can_not_destoroied'));
        }

        $interface->deleteLibrary($library);

        return redirect()
            ->route('h5p.library.index')
            ->with('success', trans('laravel-h5p.library.destroyed'));
    }

    public function clear(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;

        // Do as many as we can in five seconds.
        $start = microtime(true);
        $contents = H5pContent::where('filtered', '')->get();

        $done = 0;

        foreach ($contents as $content) {
            $content = $core->loadContent($content->id);
            $core->filterParameters($content);
            $done++;
            if ((microtime(true) - $start) > 5) {
                break;
            }
        }

        $count = intval(count($contents) - $done);

        return redirect()->route('h5p.library.index')
            ->with('success', trans('laravel-h5p.library.cleared'));
    }

    public function restrict(Request $request)
    {
        $entry = H5pLibrary::where('id', $request->get('id'))->first();

        if ($entry) {
            if ($entry->restricted == '1') {
                $entry->restricted = '0';
            } else {
                $entry->restricted = '1';
            }
            $entry->update();
        }

        return response()->json($entry);
    }

    private function assets($scripts = [], $styles = [])
    {
        $prefix = 'assets/vendor/h5p/h5p-core/';
        $return = [
            'scripts' => [],
            'styles'  => [],
        ];

        foreach (H5PCore::$adminScripts as $script) {
            $return['scripts'][] = $prefix.$script;
        }

        $return['styles'][] = $prefix.'styles/h5p.css';
        $return['styles'][] = $prefix.'styles/h5p-admin.css';

        if ($scripts) {
            foreach ($scripts as $script) {
                $return['scripts'][] = $prefix.$script;
            }
        }
        if ($styles) {
            foreach ($styles as $style) {
                $return['styles'][] = $prefix.$style;
            }
        }

        return $return;
    }

    //@TODO The following is a feature from the existing WordPress plug-in, but not all features need to be developed.
    // Then connect to the new method as needed and implement it
    //https://github.com/h5p/h5p-wordpress-plugin/blob/90a7bb4fa3d927eda401470bc599c9f1d7508ffe/admin/class-h5p-library-admin.php
    //----------------------------------------------------------------------------------

    /**
     * Load library.
     *
     * @since 1.1.0
     *
     * @param int $id optional
     */
    private function get_library($id = null)
    {
//        if ($this->library !== NULL) {
        //            return $this->library; // Return the current loaded library.
        //        }
        if ($id === null) {
            $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        }

        // Try to find content with $id.
        return H5pLibrary::findOrFail($id);
    }

    /**
     * Display admin interface for managing content libraries.
     *
     * @since 1.1.0
     */
    public function display_libraries_page()
    {
        switch (filter_input(INPUT_GET, 'task', FILTER_SANITIZE_STRING)) {
        case null:
            $this->display_libraries();

            return;
        case 'show':
            $this->display_library_details();

            return;
        case 'delete':
            $library = $this->get_library();
            H5P_Plugin_Admin::print_messages();
            if ($library) {
                include_once 'views/library-delete.php';
            }

            return;
        case 'upgrade':
            $library = $this->get_library();
            if ($library) {
                $settings = $this->display_content_upgrades($library);
            }
            include_once 'views/library-content-upgrade.php';
            if (isset($settings)) {
                $h5p = H5P_Plugin::get_instance();
                $h5p->print_settings($settings, 'H5PAdminIntegration');
            }

            return;
        }
        echo '<div class="wrap"><h2>'.esc_html__('Unknown task.').'</h2></div>';
    }

    /**
     * JavaScript settings needed to rebuild content caches.
     *
     * @since 1.1.0
     */
    private function get_not_cached_settings($num)
    {
        return [
            'num'      => $num,
            'url'      => route('h5p.ajax.rebuild-cache'),
            'message'  => __('Not all content has gotten their cache rebuilt. This is required to be able to delete libraries, and to display how many contents that uses the library.'),
            'progress' => __('1 content need to get its cache rebuilt. :num contents needs to get their cache rebuilt.', ['num' => $num]),
            //            'button' => __('Rebuild cache')
        ];
    }
}
