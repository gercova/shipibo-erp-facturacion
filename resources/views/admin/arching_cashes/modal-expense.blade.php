<div class="modal fade" id="modalRegisterExpense" tabindex="-1" aria-labelledby="modalRegisterExpenseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold" id="modalRegisterExpenseLabel">
                    <i class="ri-money-dollar-circle-line me-1"></i> Registrar Gasto / Egreso de Caja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRegisterExpense">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="ri-information-line me-1"></i>
                        Este egreso se registrará para la caja activa del día de hoy y afectará el balance de efectivo.
                    </div>

                    <div class="mb-3">
                        <label for="expense_motivo" class="form-label fw-semibold text-dark">
                            Motivo o Concepto del Gasto <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="expense_motivo" name="motivo" 
                               placeholder="Ej. Artículos de limpieza, Pago de flete, Útiles de oficina..." required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="expense_monto" class="form-label fw-semibold text-dark">
                                Monto ({{ $signo }}) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">{{ $signo }}</span>
                                <input type="number" step="0.01" min="0.01" class="form-control fw-bold text-danger text-end" 
                                       id="expense_monto" name="monto" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="expense_metodo_pago" class="form-label fw-semibold text-dark">
                                Medio de Pago
                            </label>
                            <select class="form-select" id="expense_metodo_pago" name="metodo_pago">
                                <option value="Efectivo" selected>Efectivo (Caja)</option>
                                <option value="Yape">Yape</option>
                                <option value="Plin">Plin</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Tarjeta">Tarjeta</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="expense_tipo_comprobante" class="form-label fw-semibold text-dark">
                                Tipo de Comprobante
                            </label>
                            <select class="form-select" id="expense_tipo_comprobante" name="tipo_comprobante">
                                <option value="RECIBO" selected>Recibo Simple</option>
                                <option value="TICKET">Ticket</option>
                                <option value="BOLETA">Boleta</option>
                                <option value="FACTURA">Factura</option>
                                <option value="VALE">Vale de Caja</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="expense_nro_comprobante" class="form-label fw-semibold text-dark">
                                N° Comprobante
                            </label>
                            <input type="text" class="form-control" id="expense_nro_comprobante" name="nro_comprobante" 
                                   placeholder="Opcional (Ej. REC-001)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="expense_beneficiario" class="form-label fw-semibold text-dark">
                            Beneficiario / Proveedor
                        </label>
                        <input type="text" class="form-control" id="expense_beneficiario" name="beneficiario" 
                               placeholder="Nombre o razón social (Opcional)">
                    </div>

                    <div class="mb-0">
                        <label for="expense_observaciones" class="form-label fw-semibold text-dark">
                            Observaciones adicionales
                        </label>
                        <textarea class="form-control" id="expense_observaciones" name="observaciones" rows="2" 
                                  placeholder="Detalle o nota adicional (Opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger px-4" id="btnSubmitExpense">
                        <i class="ri-save-line me-1"></i> Registrar Gasto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
