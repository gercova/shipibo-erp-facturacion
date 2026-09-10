@extends('admin.layout')

@section('styles')
    <style>
        /* Estilos complementarios para alineación perfecta con SB Admin Pro */
        .img-company-logo {
            max-width: 140px;
            max-height: 140px;
            object-fit: contain;
        }

        .logo-preview-box {
            width: 150px;
            height: 150px;
            border: 1px dashed #cbd5e1;
            background-color: #f8fafc;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            padding: 0.5rem;
        }

        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid #c5ccd6 !important;
            border-radius: 0.35rem !important;
            padding: 4px 8px !important;
            display: flex;
            align-items: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #363d47 !important;
            font-size: 0.875rem !important;
            line-height: normal !important;
            padding-left: 0 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        [data-bs-theme="dark"] .logo-preview-box {
            background-color: #1e293b;
            border-color: #334155;
        }
    </style>
@endsection

@section('content')
    <!-- Page Header (Patrón SB Admin Pro) -->
    <header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
        <div class="container-xl px-4">
            <div class="page-header-content">
                <div class="row align-items-center justify-content-between pt-3">
                    <div class="col-auto mb-3">
                        <h1 class="page-header-title">
                            <div class="page-header-icon"><i data-feather="settings"></i></div>
                            Configuración de Empresa
                        </h1>
                    </div>
                    <div class="col-12 col-xl-auto mb-3">
                        <ol class="breadcrumb m-0 fs-13">
                            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}" class="text-decoration-none">Sistema</a></li>
                            <li class="breadcrumb-item active">Empresa</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </header>

    @php
        $logoUrl = !empty($empresa->logo) ? asset('files/logos/' . $empresa->logo) : asset('files/empty_logo.png');
        $isSunatProd = (string) $empresa->servidor_sunat === '1';
        $hasCert = !empty($empresa->certificado);
        $certName = $hasCert ? basename($empresa->certificado) : null;
    @endphp

    <!-- Main Content Container -->
    <div class="container-xl px-4 mt-2">
        <!-- Account Page Navigation (Patrón nativo .nav-borders de SB Admin Pro) -->
        <nav class="nav nav-borders" role="tablist">
            <a class="nav-link active ms-0" data-bs-toggle="tab" href="#personal" role="tab" aria-selected="true">
                <i class="fas fa-building me-1"></i> Datos de la Empresa
            </a>
            <a class="nav-link" data-bs-toggle="tab" href="#sunat_tab" role="tab" aria-selected="false">
                <i class="fas fa-shield-alt me-1"></i> Facturación & SUNAT
            </a>
        </nav>
        <hr class="mt-0 mb-4">

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- =========================================================
                 TAB 1: DATOS DE LA EMPRESA
                 ========================================================= -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                <form id="form-info" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Columna Izquierda: Logo y Ficha Informativa -->
                        <div class="col-xl-4">
                            <!-- Card de Logotipo -->
                            <div class="card mb-4">
                                <div class="card-header">Logotipo de la Empresa</div>
                                <div class="card-body text-center">
                                    <div class="logo-preview-box">
                                        <img id="preview-logo" class="img-company-logo" src="{{ $logoUrl }}" alt="Logo de la Empresa">
                                    </div>
                                    <div class="small font-italic text-muted mb-3">JPG, JPEG o PNG no mayor a 2MB</div>
                                    <label class="btn btn-primary btn-sm mb-0" for="company_logo" style="cursor: pointer;">
                                        <i class="fas fa-upload me-1"></i> Seleccionar imagen
                                    </label>
                                    <input class="d-none" id="company_logo" type="file" name="logo" accept="image/jpeg,.jpg,.jpeg,image/png,.png">
                                    <div id="company-logo-selected-text" class="small text-muted mt-2"></div>
                                </div>
                            </div>

                            <!-- Card de Ficha Rápida -->
                            <div class="card mb-4">
                                <div class="card-header">Ficha de la Empresa</div>
                                <div class="card-body">
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="mb-2 d-flex justify-content-between align-items-start">
                                            <span class="text-muted">Razón Social:</span>
                                            <span class="fw-bold text-end ms-2" id="card-summary-name">{{ $empresa->razon_social }}</span>
                                        </li>
                                        <li class="mb-2 d-flex justify-content-between align-items-center">
                                            <span class="text-muted">RUC:</span>
                                            <span class="fw-bold" id="card-summary-ruc">{{ $empresa->ruc }}</span>
                                        </li>
                                        <li class="mb-2 d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Estado RUC:</span>
                                            <span class="badge bg-success-soft text-success">Activo / Habido</span>
                                        </li>
                                        <li class="mb-2 d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Régimen Impuesto:</span>
                                            <span class="badge {{ $empresa->cobrar_igv ? 'bg-primary-soft text-primary' : 'bg-info-soft text-info' }}" id="summary-igv-badge">
                                                {{ $empresa->cobrar_igv ? 'General (18%)' : 'Ley Amazonía' }}
                                            </span>
                                        </li>
                                        <li class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Servidor SUNAT:</span>
                                            <span class="badge {{ $isSunatProd ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning' }}" id="summary-sunat-badge">
                                                {{ $isSunatProd ? 'Producción' : 'Beta / Pruebas' }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Formulario de Información -->
                        <div class="col-xl-8">
                            <div class="card mb-4">
                                <div class="card-header">Información General y Domicilio Fiscal</div>
                                <div class="card-body">
                                    <!-- RUC & Teléfono -->
                                    <div class="row gx-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="inputRuc">RUC <span class="text-danger">*</span></label>
                                            <input class="form-control" id="inputRuc" type="text" name="ruc" value="{{ $empresa->ruc }}" maxlength="11" placeholder="Número de RUC" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="inputTelefono">Teléfono de Contacto</label>
                                            <input class="form-control" id="inputTelefono" type="text" name="telefono" value="{{ $empresa->telefono }}" placeholder="Ej. +51 999 999 999">
                                        </div>
                                    </div>

                                    <!-- Razón Social & Nombre Comercial -->
                                    <div class="row gx-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="inputRazonSocial">Razón Social <span class="text-danger">*</span></label>
                                            <input class="form-control text-uppercase" id="inputRazonSocial" type="text" name="razon_social" value="{{ $empresa->razon_social }}" placeholder="Nombre o Razón Social" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="inputNombreComercial">Nombre Comercial</label>
                                            <input class="form-control text-uppercase" id="inputNombreComercial" type="text" name="nombre_comercial" value="{{ $empresa->nombre_comercial }}" placeholder="Nombre Comercial">
                                        </div>
                                    </div>

                                    <!-- Dirección Fiscal & País -->
                                    <div class="row gx-3 mb-3">
                                        <div class="col-md-8">
                                            <label class="small mb-1 fw-bold" for="inputDireccion">Dirección Fiscal <span class="text-danger">*</span></label>
                                            <input class="form-control text-uppercase" id="inputDireccion" type="text" name="direccion" value="{{ $empresa->direccion }}" placeholder="Dirección completa" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small mb-1 fw-bold" for="selectPais">País</label>
                                            <select class="form-select" id="selectPais" name="pais" disabled>
                                                <option value="PE" selected>Perú</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Ubigeo: Departamento, Provincia, Distrito -->
                                    <div class="row gx-3 mb-3">
                                        <div class="col-md-4">
                                            <label class="small mb-1 fw-bold">Departamento</label>
                                            <select name="departamento" class="form-control select2_department" style="width: 100%;"></select>
                                        </div>
                                        <div id="wrapper_province" class="col-md-4 d-none">
                                            <label class="small mb-1 fw-bold">Provincia</label>
                                            <select name="provincia" class="form-control select2_province" style="width: 100%;"></select>
                                        </div>
                                        <div id="wrapper_district" class="col-md-4 d-none">
                                            <label class="small mb-1 fw-bold">Distrito</label>
                                            <select name="distrito" class="form-control select2_district" style="width: 100%;"></select>
                                        </div>
                                    </div>

                                    <!-- Urbanización & Local -->
                                    <div class="row gx-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="inputUrbanizacion">Urbanización / Zona</label>
                                            <input class="form-control text-uppercase" id="inputUrbanizacion" type="text" name="urbanizacion" value="{{ $empresa->urbanizacion }}" placeholder="Urbanización">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="inputLocal">Local / Establecimiento Anexo</label>
                                            <input class="form-control text-uppercase" id="inputLocal" type="text" name="local" value="{{ $empresa->local }}" placeholder="Ej. 0000 (Principal)">
                                        </div>
                                    </div>

                                    <!-- Switch de IGV -->
                                    <div class="mb-4">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                                                <div>
                                                    <label class="form-check-label fw-bold text-dark d-block" for="cobrar_igv">
                                                        Estado del Impuesto: <span id="status-igv" class="{{ $empresa->cobrar_igv ? 'text-primary' : 'text-success' }}">
                                                            {{ $empresa->cobrar_igv ? 'Régimen General (18%)' : 'Exonerado (Ley Amazonía)' }}
                                                        </span>
                                                    </label>
                                                    <small id="desc-igv" class="text-muted">
                                                        {{ $empresa->cobrar_igv ? 'Se desglosará el 18% de IGV en los comprobantes de pago emitidos.' : 'Exonerado de IGV conforme a la Ley N° 27037 (Ley de Promoción de la Inversión en la Amazonía).' }}
                                                    </small>
                                                </div>
                                                <input class="form-check-input ms-0" type="checkbox" role="switch" id="cobrar_igv" name="cobrar_igv"
                                                    style="width: 48px; height: 24px; cursor: pointer;"
                                                    {{ $empresa->cobrar_igv ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Botón de Envío -->
                                    <div class="d-flex justify-content-end">
                                        <button class="btn btn-primary px-4 btn-save-info" type="button">
                                            <i class="fas fa-save me-1"></i> Guardar cambios
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- =========================================================
                 TAB 2: FACTURACIÓN ELECTRÓNICA & SUNAT
                 ========================================================= -->
            <div class="tab-pane fade" id="sunat_tab" role="tabpanel">
                <form id="form_info_user" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Columna Izquierda: Entorno y Certificado -->
                        <div class="col-xl-5">
                            <!-- Card de Entorno SUNAT -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Entorno de Emisión SUNAT</span>
                                    <span id="sunat-environment-badge" class="badge {{ $isSunatProd ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning' }}">
                                        {{ $isSunatProd ? 'Producción' : 'Beta' }}
                                    </span>
                                </div>
                                <div class="card-body">
                                    <p id="sunat-environment-text" class="small text-muted mb-3">
                                        {{ $isSunatProd ? 'Listo para entorno productivo oficial.' : 'Modo pruebas activo para integraciones y validaciones.' }}
                                    </p>
                                    <label class="small fw-bold text-muted text-uppercase mb-2 d-block">Seleccionar Servidor</label>
                                    <div class="btn-group w-100 shadow-none" role="group">
                                        <input type="radio" class="btn-check" name="servidor_sunat" id="beta" value="3" {{ !$isSunatProd ? 'checked' : '' }}>
                                        <label class="btn btn-outline-warning d-flex align-items-center justify-content-center gap-2 py-2" for="beta">
                                            <i class="fas fa-vial"></i>
                                            <span>Beta / Pruebas</span>
                                        </label>

                                        <input type="radio" class="btn-check" name="servidor_sunat" id="prod" value="1" {{ $isSunatProd ? 'checked' : '' }}>
                                        <label class="btn btn-outline-success d-flex align-items-center justify-content-center gap-2 py-2" for="prod">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Producción</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Card de Certificado Digital -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Certificado Digital</span>
                                    <span id="certificate-status-badge" class="badge {{ $hasCert ? 'bg-success-soft text-success' : 'bg-secondary-soft text-secondary' }}">
                                        {{ $hasCert ? 'Cargado' : 'Pendiente' }}
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small mb-1 fw-bold">Certificado Actual</label>
                                        <div id="certificate-current-badge" class="form-control bg-light text-truncate">
                                            {{ $certName ?: 'Sin certificado cargado' }}
                                        </div>
                                        <div id="certificate-status-text" class="small text-muted d-none">
                                            {{ $certName ?: 'Aun no se ha cargado un certificado digital.' }}
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="small mb-1 fw-bold" for="certificado">Cargar nuevo certificado (.pfx)</label>
                                        <input class="form-control" type="file" id="certificado" name="certificado" accept=".pfx,application/x-pkcs12">
                                        <small id="certificate-selected-name" class="text-primary d-block mt-1 small"></small>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fas fa-info-circle me-1 text-primary"></i> El archivo debe estar en formato <code>.pfx</code> con clave privada para la firma digital de comprobantes.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Credenciales SOL y GRE -->
                        <div class="col-xl-7">
                            <div class="card mb-4">
                                <div class="card-header">Credenciales de Acceso SUNAT</div>
                                <div class="card-body">
                                    <!-- Nombre Comercial -->
                                    <div class="mb-3">
                                        <label class="small mb-1 fw-bold" for="inputNombreComercialSunat">Nombre Comercial</label>
                                        <input class="form-control text-uppercase" id="inputNombreComercialSunat" type="text" name="nombre_comercial" value="{{ $empresa->nombre_comercial }}" placeholder="Nombre Comercial en Comprobantes">
                                    </div>

                                    <!-- Usuario SOL & Clave SOL -->
                                    <div class="row gx-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="inputUsuarioSunat">Usuario Secundario (SOL)</label>
                                            <input class="form-control" id="inputUsuarioSunat" type="text" name="usuario_sunat" value="{{ $empresa->usuario_sunat }}" placeholder="Ej. MODDATOS">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="input_clave_sunat">Clave SOL</label>
                                            <div class="input-group">
                                                <input class="form-control border-end-0" id="input_clave_sunat" type="password" name="clave_sunat" value="{{ $empresa->clave_sunat }}" placeholder="••••••••">
                                                <button class="btn border border-start-0 bg-light btn-toggle-pwd" type="button" data-target="input_clave_sunat" style="border-radius: 0 0.35rem 0.35rem 0;">
                                                    <i class="fas fa-eye text-muted"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Clave Certificado & Vencimiento -->
                                    <div class="row gx-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="input_clave_certificado">Clave del Certificado</label>
                                            <div class="input-group">
                                                <input class="form-control border-end-0" id="input_clave_certificado" type="password" name="clave_certificado" value="{{ $empresa->clave_certificado }}" placeholder="••••••••">
                                                <button class="btn border border-start-0 bg-light btn-toggle-pwd" type="button" data-target="input_clave_certificado" style="border-radius: 0 0.35rem 0.35rem 0;">
                                                    <i class="fas fa-eye text-muted"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="inputVencimiento">Vencimiento del Certificado</label>
                                            <input class="form-control" id="inputVencimiento" type="date" name="vencimiento_certificado" value="{{ optional($empresa->vencimiento_certificado)->format('Y-m-d') }}">
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <h6 class="text-primary fw-bold mb-3">
                                        <i class="fas fa-truck me-1"></i> Guías de Remisión Electrónica (GRE API SUNAT)
                                    </h6>

                                    <!-- GRE Client ID & Secret -->
                                    <div class="row gx-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="inputGreId">GRE Client ID</label>
                                            <input class="form-control" id="inputGreId" type="text" name="gre_client_id" value="{{ $empresa->gre_client_id }}" placeholder="Credencial GRE">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small mb-1 fw-bold" for="input_gre_client_secret">GRE Client Secret</label>
                                            <div class="input-group">
                                                <input class="form-control border-end-0" id="input_gre_client_secret" type="password" name="gre_client_secret" value="{{ $empresa->gre_client_secret }}" placeholder="Secreto GRE">
                                                <button class="btn border border-start-0 bg-light btn-toggle-pwd" type="button" data-target="input_gre_client_secret" style="border-radius: 0 0.35rem 0.35rem 0;">
                                                    <i class="fas fa-eye text-muted"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Botón de Envío -->
                                    <div class="d-flex justify-content-end">
                                        <button class="btn btn-primary px-4 btn-save-user" type="button">
                                            <i class="fas fa-shield-alt me-1"></i>
                                            <span>Actualizar Credenciales</span>
                                            <span class="text-save-user d-none">Actualizando...</span>
                                            <span class="spinner-border spinner-border-sm d-none text-saving-user"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('admin.business.js-home')
@endsection
