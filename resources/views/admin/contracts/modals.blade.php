<!-- Modal Detail Contract -->
<div class="modal fade" id="modalDetailContract" tabindex="-1" aria-labelledby="modalDetailContractLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title d-flex align-items-center" id="modalDetailContractLabel">
                    <i class="ri-file-text-line me-2"></i>
                    Contrato: <span id="modal-contract-number" class="ms-2 badge bg-white text-primary"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Title & Status Banner -->
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h5 id="modal-contract-title" class="fw-bold mb-0 text-dark"></h5>
                    <span id="modal-status-badge" class="badge bg-success"></span>
                </div>

                <!-- Parties Info Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-primary mb-2"><i class="ri-user-star-line me-1"></i> Prestador de Servicios</h6>
                            <div class="small">
                                <div><strong>Empresa:</strong> <span id="modal-provider-name"></span></div>
                                <div><strong>RUC / Doc:</strong> <span id="modal-provider-doc"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-success mb-2"><i class="ri-user-3-line me-1"></i> Cliente Contratante</h6>
                            <div class="small">
                                <div><strong>Nombre:</strong> <span id="modal-client-name"></span></div>
                                <div><span id="modal-client-doc"></span></div>
                                <div><strong>Dirección:</strong> <span id="modal-client-address"></span></div>
                                <div><strong>Teléfono:</strong> <span id="modal-client-phone"></span> | <strong>Email:</strong> <span id="modal-client-email"></span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Details -->
                <div class="card mb-4 border-dashed">
                    <div class="card-body p-3 bg-white">
                        <div class="row text-center g-2">
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Fecha y Hora del Evento</span>
                                <strong id="modal-event-date" class="text-primary"></strong>
                            </div>
                            <div class="col-md-5">
                                <span class="text-muted small d-block">Lugar / Dirección del Evento</span>
                                <strong id="modal-event-location" class="text-dark"></strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted small d-block">Fecha de Emisión</span>
                                <strong id="modal-issue-date" class="text-secondary"></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contracted Services / Products Table -->
                <h6 class="fw-bold mb-2 text-dark"><i class="ri-shopping-bag-3-line me-1"></i> Servicios y Productos Contratados</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th>Descripción</th>
                                <th width="15%" class="text-center">Cantidad</th>
                                <th width="20%" class="text-end">Precio Unit.</th>
                                <th width="20%" class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modal-items-body"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end fw-bold" id="modal-subtotal"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">I.G.V.:</td>
                                <td class="text-end fw-bold" id="modal-igv"></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="4" class="text-end fw-bold fs-6">TOTAL GENERAL:</td>
                                <td class="text-end fw-bold fs-6" id="modal-total"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment Schedule & Credit Installments Section -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="ri-bank-card-line me-1"></i> Cronograma de Pagos y Estado de Crédito
                    </h6>
                    <div id="modal-overdue-alert-banner" style="display: none;">
                        <span class="badge bg-danger text-white fs-7 py-1 px-2">
                            <i class="ri-alarm-warning-line me-1"></i> ¡ALERTA: TIENE CUOTAS VENCIDAS!
                        </span>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle" id="modal-table-installments">
                        <thead class="table-light">
                            <tr>
                                <th width="6%" class="text-center">#</th>
                                <th>Concepto</th>
                                <th width="12%" class="text-center">%</th>
                                <th width="22%" class="text-center">Vencimiento</th>
                                <th width="18%" class="text-end">Monto</th>
                                <th width="20%" class="text-center">Estado</th>
                                <th width="14%" class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="modal-installments-body"></tbody>
                        <tfoot class="table-light small">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total Pagado:</td>
                                <td class="text-end fw-bold text-success" id="modal-installments-paid"></td>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Saldo Pendiente:</td>
                                <td class="text-end fw-bold text-danger" id="modal-installments-pending"></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- 20% Guarantee info banner -->
                <div class="p-2 rounded-3 border bg-warning-subtle border-warning text-dark small mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-warning-emphasis">
                            <i class="ri-shield-check-line me-1"></i> Fondo de Garantía Contractual (20% - Cláusula Octava):
                        </span>
                        <strong class="text-dark" id="modal-guarantee-total"></strong>
                    </div>
                    <span class="text-muted d-block" style="font-size: 0.75rem;">
                        Estipulado para cubrir eventuales roturas o pérdidas de cristalería, barras móviles y utensilios de coctelería. Liquidable al término del evento.
                    </span>
                </div>

                <!-- Clauses Section -->
                <h6 class="fw-bold mb-2 text-dark"><i class="ri-article-line me-1"></i> Cláusulas del Contrato</h6>
                <div id="modal-clauses-container" class="p-3 bg-light rounded-3 border mb-4" style="max-height: 250px; overflow-y: auto;">
                </div>

                <!-- Signature Section -->
                <div id="modal-signature-wrapper" class="mb-2 text-center p-3 border rounded-3 bg-white">
                    <span class="text-muted small d-block mb-2 fw-bold">FIRMA DIGITAL DEL CLIENTE</span>
                    <img id="modal-signature-img" src="" alt="Firma Digital" style="max-height: 120px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 5px; background: #fff;" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a id="modal-btn-checklist" href="#" class="btn btn-outline-success">
                    <i class="ri-checkbox-multiple-line me-1"></i> Checklist del Evento
                </a>
                <a id="modal-btn-download-pdf" href="#" class="btn btn-outline-primary" target="_blank">
                    <i class="ri-download-line me-1"></i> Descargar PDF
                </a>
                <button type="button" id="modal-btn-print-pdf" class="btn btn-primary btn-print-contract">
                    <i class="ri-printer-line me-1"></i> Imprimir A4
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Quick Create Client -->
<div class="modal fade" id="modalQuickCreateClient" tabindex="-1" aria-labelledby="modalQuickCreateClientLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalQuickCreateClientLabel"><i class="ri-user-add-line me-1"></i> Registrar Cliente Rápido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-quick-client">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tipo Documento <span class="text-danger">*</span></label>
                        <select name="tipo_documento" id="quick-client-iddoc" class="form-select form-select-sm" required>
                            @foreach ($typeDocuments ?? [] as $typeDoc)
                                <option value="{{ $typeDoc->id }}">{{ $typeDoc->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Número de Documento (DNI/RUC) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="dni_ruc" id="quick-client-documento" class="form-control" placeholder="Ingrese DNI (8 dígitos) o RUC (11 dígitos)" required />
                            <button class="btn btn-primary" type="button" id="btn-quick-search-dni-ruc">
                                <i class="ri-search-line"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombres / Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="razon_social" id="quick-client-nombres" class="form-control form-control-sm" placeholder="Nombre completo o razón social" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Dirección <span class="text-danger">*</span></label>
                        <input type="text" name="direccion" id="quick-client-direccion" class="form-control form-control-sm" value="-" required />
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Teléfono</label>
                            <input type="text" name="telefono" id="quick-client-telefono" class="form-control form-control-sm" placeholder="Opcional" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" id="quick-client-email" class="form-control form-control-sm" placeholder="Opcional" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm"><i class="ri-save-line me-1"></i> Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pay Contract Installment -->
<div class="modal fade" id="modalPayContractInstallment" tabindex="-1" aria-labelledby="modalPayContractInstallmentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalPayContractInstallmentLabel">
                    <i class="ri-money-dollar-circle-line me-1"></i> Registrar Pago de Cuota
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-pay-installment">
                @csrf
                <input type="hidden" name="contract_id" id="pay-contract-id" />
                <input type="hidden" name="installment_id" id="pay-installment-id" />
                <div class="modal-body p-4">
                    <div class="alert alert-light border p-3 mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Concepto:</span>
                            <strong id="pay-installment-desc" class="text-dark"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Vencimiento:</span>
                            <span id="pay-installment-due" class="fw-bold"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Monto a Cancelar:</span>
                            <span class="fw-bold fs-5 text-success" id="pay-installment-amount"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fecha de Pago <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_pago" id="pay-installment-date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Método de Pago <span class="text-danger">*</span></label>
                        <select name="metodo_pago" id="pay-installment-method" class="form-select form-select-sm" required>
                            <option value="Efectivo" selected>Efectivo</option>
                            <option value="Transferencia BCP">Transferencia BCP</option>
                            <option value="Transferencia BBVA">Transferencia BBVA</option>
                            <option value="Transferencia Interbank">Transferencia Interbank</option>
                            <option value="Yape">Yape</option>
                            <option value="Plin">Plin</option>
                            <option value="Tarjeta Débito/Crédito">Tarjeta Débito/Crédito</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">N° de Operación / Referencia</label>
                        <input type="text" name="referencia_pago" id="pay-installment-reference" class="form-control form-control-sm" placeholder="Ej: OP-9823412" />
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Observaciones del Pago</label>
                        <textarea name="observaciones" id="pay-installment-notes" class="form-control form-control-sm" rows="2" placeholder="Opcional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm" id="btn-submit-pay-installment">
                        <i class="ri-check-line me-1"></i> Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
