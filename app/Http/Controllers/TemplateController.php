<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\TemplateCreateRequest;
use App\Http\Requests\TemplateUpdateRequest;
use App\Repositories\Interfaces\TemplateRepositoryInterface as TemplateRepository;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Template;

class TemplateController extends Controller
{
    private $templateRepository;

    public function __construct(TemplateRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = config('paginate.perPage');
        $templates = $this->templateRepository->getAllByUser($perPage);

        return view('templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TemplateCreateRequest $request)
    {
        $data = $request->only(['name', 'content', 'params', 'status']);
        $data['user_id'] = Auth::id();

        DB::beginTransaction();
        try {
            $template = $this->templateRepository->create($data);
            DB::commit();
            $request->session()->flash('messageSuccess', [
                'status' => 'Create success',
                'message' => 'This template successfully created',
            ]);

            return $template->id;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
            return redirect()->back()->with('messageFail', [
                'status' => 'Create failed',
                'message' => 'Create failed. Something went wrong',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Template $template)
    {
        return view('templates.edit', compact('template'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TemplateUpdateRequest $request, Template $template)
    {
        $data = $request->only([
            'name',
            'content',
            'params',
            'status',
        ]);
        $data['user_id'] = Auth::id();

        DB::beginTransaction();
        try {
            $template = $this->templateRepository->update($template->id, $data);

            DB::commit();
            $request->session()->flash('messageSuccess', [
                'status' => 'Update success',
                'message' => 'This template successfully updated',
            ]);

            return $template->id;
        } catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('messageFail', [
                'status' => 'Update failed',
                'message' => 'Update failed. Something went wrong',
            ])->withInput();
        }
    }

    public function destroy(Request $request, Template $template)
    {
        $page = $request->page ? ['page' => $request->page] : null;
        $this->authorize('delete', $template);

        try {
            $this->templateRepository->delete($template->id);

            return redirect()->route('templates.index', $page)->with('messageSuccess', [
                'status' => 'Delete success',
                'message' => __('message.notification.delete.success', ['object' => 'template']),
            ]);
        } catch (Exception $exception) {
            return redirect()->back()->with('messageFail', [
                'status' => 'Delete failed',
                'message' => __('message.template.notification.delete.fail'),
            ]);
        }
    }
}
