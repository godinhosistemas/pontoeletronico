<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TimeEntryFile;
use App\Services\AFDService;
use App\Services\AEJService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

/**
 * Controller para geração e download de arquivos legais (AFD e AEJ)
 */
class LegalFilesController extends Controller
{
    private AFDService $afdService;
    private AEJService $aejService;

    public function __construct(AFDService $afdService, AEJService $aejService)
    {
        $this->afdService = $afdService;
        $this->aejService = $aejService;
    }

    /**
     * Lista todos os arquivos gerados
     */
    public function index(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $query = TimeEntryFile::where('tenant_id', $tenant->id)
            ->with(['generatedBy', 'employee'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('period_start')) {
            $query->where('period_start', '>=', $request->period_start);
        }

        if ($request->filled('period_end')) {
            $query->where('period_end', '<=', $request->period_end);
        }

        $files = $query->paginate(20);

        return view('admin.legal-files.index', compact('files'));
    }

    /**
     * Gera arquivo AFD
     */
    public function generateAFD(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        try {
            $tenant = Auth::user()->tenant;

            $file = $this->afdService->generateAFD(
                $tenant,
                $request->period_start,
                $request->period_end,
                Auth::id()
            );

            if (!$file) {
                return back()->with('error', 'Não foi possível gerar o arquivo AFD. Verifique se existem marcações no período.');
            }

            return back()->with('success', 'Arquivo AFD gerado com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar AFD: ' . $e->getMessage());
            return back()->with('error', 'Erro ao gerar arquivo AFD: ' . $e->getMessage());
        }
    }

    /**
     * Gera arquivo AEJ para um funcionário específico
     */
    public function generateAEJ(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        try {
            $tenant = Auth::user()->tenant;
            $employee = Employee::where('id', $request->employee_id)
                ->where('tenant_id', $tenant->id)
                ->firstOrFail();

            $file = $this->aejService->generateAEJ(
                $employee,
                $request->period_start,
                $request->period_end,
                Auth::id()
            );

            if (!$file) {
                return back()->with('error', 'Não foi possível gerar o arquivo AEJ. Verifique se existem marcações no período.');
            }

            return back()->with('success', 'Arquivo AEJ gerado com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar AEJ: ' . $e->getMessage());
            return back()->with('error', 'Erro ao gerar arquivo AEJ: ' . $e->getMessage());
        }
    }

    /**
     * Gera arquivos AEJ para todos os funcionários
     */
    public function generateBulkAEJ(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        try {
            $tenant = Auth::user()->tenant;

            $files = $this->aejService->generateBulkAEJ(
                $tenant,
                $request->period_start,
                $request->period_end,
                Auth::id()
            );

            $count = count($files);

            if ($count === 0) {
                return back()->with('warning', 'Nenhum arquivo AEJ foi gerado. Verifique se existem marcações no período.');
            }

            return back()->with('success', "$count arquivo(s) AEJ gerado(s) com sucesso!");

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar AEJs em lote: ' . $e->getMessage());
            return back()->with('error', 'Erro ao gerar arquivos AEJ: ' . $e->getMessage());
        }
    }

    /**
     * Download do arquivo principal (.txt)
     */
    public function download(TimeEntryFile $file)
    {
        $this->authorize('view', $file);

        $content = $file->getFileContent();

        if (!$content) {
            abort(404, 'Arquivo não encontrado');
        }

        // Incrementa contador de downloads
        $file->incrementDownloadCount();

        return Response::make($content, 200, [
            'Content-Type' => 'text/plain; charset=ISO-8859-1',
            'Content-Disposition' => 'attachment; filename="' . $file->getDownloadFileName() . '"',
            'Content-Length' => strlen($content),
        ]);
    }

    /**
     * Download do arquivo de assinatura (.p7s)
     */
    public function downloadSignature(TimeEntryFile $file)
    {
        $this->authorize('view', $file);

        if (!$file->isSigned()) {
            abort(404, 'Arquivo de assinatura não encontrado');
        }

        $content = $file->getSignatureContent();

        if (!$content) {
            abort(404, 'Arquivo de assinatura não encontrado');
        }

        return Response::make($content, 200, [
            'Content-Type' => 'application/pkcs7-signature',
            'Content-Disposition' => 'attachment; filename="' . $file->getSignatureFileName() . '"',
            'Content-Length' => strlen($content),
        ]);
    }

    /**
     * Download de ambos os arquivos (txt + p7s) em ZIP
     */
    public function downloadBundle(TimeEntryFile $file)
    {
        $this->authorize('view', $file);

        $content = $file->getFileContent();
        if (!$content) {
            abort(404, 'Arquivo não encontrado');
        }

        // Cria arquivo ZIP temporário
        $zipPath = storage_path('app/temp/' . uniqid('bundle_') . '.zip');
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Erro ao criar arquivo ZIP');
        }

        // Adiciona arquivo principal
        $zip->addFromString($file->getDownloadFileName(), $content);

        // Adiciona assinatura se disponível
        if ($file->isSigned()) {
            $signatureContent = $file->getSignatureContent();
            if ($signatureContent) {
                $zip->addFromString($file->getSignatureFileName(), $signatureContent);
            }
        }

        $zip->close();

        // Incrementa contador
        $file->incrementDownloadCount();

        $zipFileName = str_replace('.txt', '.zip', $file->getDownloadFileName());

        return Response::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Deleta um arquivo gerado
     */
    public function destroy(TimeEntryFile $file)
    {
        $this->authorize('delete', $file);

        try {
            $file->delete(); // Os arquivos físicos são deletados automaticamente (boot method)

            return back()->with('success', 'Arquivo excluído com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro ao deletar arquivo: ' . $e->getMessage());
            return back()->with('error', 'Erro ao excluir arquivo: ' . $e->getMessage());
        }
    }

    /**
     * Exibe informações detalhadas de um arquivo
     */
    public function show(TimeEntryFile $file)
    {
        $this->authorize('view', $file);

        $file->load(['tenant', 'generatedBy', 'employee']);

        return view('admin.legal-files.show', compact('file'));
    }

    /**
     * API: Retorna estatísticas de arquivos gerados
     */
    public function statistics(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $stats = [
            'total_afd' => TimeEntryFile::where('tenant_id', $tenant->id)
                ->where('file_type', 'AFD')
                ->count(),

            'total_aej' => TimeEntryFile::where('tenant_id', $tenant->id)
                ->where('file_type', 'AEJ')
                ->count(),

            'total_signed' => TimeEntryFile::where('tenant_id', $tenant->id)
                ->where('is_signed', true)
                ->count(),

            'total_downloads' => TimeEntryFile::where('tenant_id', $tenant->id)
                ->sum('download_count'),

            'latest' => TimeEntryFile::where('tenant_id', $tenant->id)
                ->with(['generatedBy'])
                ->latest()
                ->first(),
        ];

        return response()->json($stats);
    }
}
