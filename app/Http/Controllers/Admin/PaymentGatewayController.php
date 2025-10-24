<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentGatewayController extends Controller
{
    /**
     * Lista gateways de pagamento
     */
    public function index()
    {
        return view('admin.payment-gateways.index');
    }

    /**
     * Exibe formulário de criação
     */
    public function create()
    {
        return view('admin.payment-gateways.create');
    }

    /**
     * Armazena novo gateway
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:asaas,mercadopago,pagarme',
            'api_key' => 'required|string',
            'api_secret' => 'nullable|string',
            'environment' => 'required|in:sandbox,production',
            'supported_methods' => 'required|array',
            'supported_methods.*' => 'in:boleto,pix,credit_card,debit_card',
            'fee_percentage' => 'nullable|numeric|min:0|max:100',
            'fee_fixed' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // Gerar slug
        $validated['slug'] = Str::slug($validated['name']);

        // Se marcado como padrão, desmarcar outros
        if ($validated['is_default'] ?? false) {
            PaymentGateway::where('is_default', true)->update(['is_default' => false]);
        }

        $gateway = PaymentGateway::create($validated);

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', 'Gateway de pagamento criado com sucesso!');
    }

    /**
     * Exibe formulário de edição
     */
    public function edit(PaymentGateway $paymentGateway)
    {
        return view('admin.payment-gateways.edit', compact('paymentGateway'));
    }

    /**
     * Atualiza gateway
     */
    public function update(Request $request, PaymentGateway $paymentGateway)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:asaas,mercadopago,pagarme',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'environment' => 'required|in:sandbox,production',
            'supported_methods' => 'required|array',
            'supported_methods.*' => 'in:boleto,pix,credit_card,debit_card',
            'fee_percentage' => 'nullable|numeric|min:0|max:100',
            'fee_fixed' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // Gerar slug
        $validated['slug'] = Str::slug($validated['name']);

        // Se marcado como padrão, desmarcar outros
        if ($validated['is_default'] ?? false) {
            PaymentGateway::where('id', '!=', $paymentGateway->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        // Se api_key não foi enviada, manter a atual
        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        // Se api_secret não foi enviada, manter a atual
        if (empty($validated['api_secret'])) {
            unset($validated['api_secret']);
        }

        $paymentGateway->update($validated);

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', 'Gateway de pagamento atualizado com sucesso!');
    }

    /**
     * Remove gateway
     */
    public function destroy(PaymentGateway $paymentGateway)
    {
        // Verificar se tem pagamentos
        if ($paymentGateway->payments()->exists()) {
            return back()->with('error', 'Não é possível excluir gateway com pagamentos associados.');
        }

        $paymentGateway->delete();

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', 'Gateway de pagamento removido com sucesso!');
    }

    /**
     * Alterna status ativo/inativo
     */
    public function toggleActive(PaymentGateway $paymentGateway)
    {
        $paymentGateway->update([
            'is_active' => !$paymentGateway->is_active,
        ]);

        $status = $paymentGateway->is_active ? 'ativado' : 'desativado';

        return back()->with('success', "Gateway {$status} com sucesso!");
    }

    /**
     * Define como padrão
     */
    public function setDefault(PaymentGateway $paymentGateway)
    {
        // Desmarcar outros como padrão
        PaymentGateway::where('is_default', true)->update(['is_default' => false]);

        // Marcar este como padrão e ativo
        $paymentGateway->update([
            'is_default' => true,
            'is_active' => true,
        ]);

        return back()->with('success', 'Gateway definido como padrão!');
    }
}
