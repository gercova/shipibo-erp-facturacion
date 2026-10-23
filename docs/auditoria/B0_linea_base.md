# Informe de Auditoría de Línea Base (B0 Baseline Audit)
## Shipibo ERP: Sistema Integral de Gestión Comercial, Control Multi-Almacén, Eventos y Facturación Electrónica SUNAT

---

## Control del Documento

| Campo | Detalle |
| :--- | :--- |
| **Documento** | Auditoría de Línea Base de Arquitectura, Integridad y Reglas de Negocio (B0) |
| **Archivo** | `docs/auditoria/B0_linea_base.md` |
| **Fecha de Auditoría** | 22 de Octubre de 2026 |
| **Auditor** | Senior Laravel Software Engineer |
| **Documentos de Referencia** | `docs/PDR_SISTEMA_COMPLETO.md` (v3.0.0), `README.md`, `docs/PRD_MODULOS_PENDIENTES_FE.md` |
| **Modo de Ejecución** | Solo Lectura (Read-Only) - Sin modificación de código de aplicación |
| **Estado** | Completado / Listo para Revisión |

---

## 1. Resumen Ejecutivo

Se ha realizado una auditoría exhaustiva de solo lectura sobre el 100% de la base de código del proyecto **Shipibo ERP** (`app/`, `database/`, `routes/`, `resources/views/`), contrastándola contra las especificaciones del **PDR Oficial** (`docs/PDR_SISTEMA_COMPLETO.md`), el **README del Repositorio** y el histórico del **PRD Legado** (`docs/PRD_MODULOS_PENDIENTES_FE.md`).

### Hallazgos Principales a Nivel Ejecutivo:
1. **Solidez del Core Tributario y Contratos**: El motor de facturación electrónica UBL 2.1 (`app/Services/Ebilling/`), el punto de venta táctil (`PosController`), y los módulos de Contratos con firma digital (`ContractController`) y Checklists para tablets (`EventChecklistController`) presentan una arquitectura base funcional y robusta.
2. **Brecha Crítica de Stock en Devoluciones y Anulaciones**:
   - La emisión de Notas de Crédito electrónicas (`BillingController`) **no restituye el inventario** a `stock_products`.
   - El retorno de checklists de eventos (`EventChecklistController`) **no afecta ni concilia el stock físico** de menaje/insumos; es meramente informativo.
   - La anulación de Notas de Venta (`SaleNoteController`) no utiliza transacciones ni bloqueos, y castea cantidades a enteros (`intval`), destruyendo decimales de stock fraccionario.
3. **Vulnerabilidades de Seguridad y Control de Acceso**:
   - Las rutas de mutación de Países (`/countries/save`, `/store`, `/delete`) carecen de middleware de autenticación y permisos.
   - Los endpoints de la API SUNAT en `routes/api.php` (`/api/sunat/*`) están **completamente abiertos y sin autenticación**.
   - Existe un **Token JWT Bearer privado para Factiliza API hardcodeado** en el controlador base (`Controller.php:27`).
4. **Desconexión en Reportes Comerciales**: Los reportes generales de ventas (`/reportes/ventas` y `/by-product`) y de medios de pago (`/reportes/pagos`) consultan **únicamente** la tabla `sale_notes`, ignorando por completo todas las Facturas y Boletas (`billings`), distorsionando la realidad financiera del negocio.

---

## 2. Verificación de Versiones, Modelos y Migraciones (Ítem 6)

Contrastación entre el entorno real de ejecución, los descriptores de dependencias y la documentación del `README.md`:

| Componente | Documentado en `README.md` | Valor Real en Código / Entorno | Estado | Hallazgo / Discrepancia |
| :--- | :--- | :--- | :---: | :--- |
| **PHP Version** | `PHP 8.2+ / 8.3.6` | `PHP 8.3.6 (cli)` / `composer.json: ^8.1` | ✅ Conforme | Runtime optimizado en PHP 8.3.6; composer permite `^8.1`. |
| **Laravel Framework** | `Laravel 10.x` | `v10.50.2` (fijado en `composer.lock`) | ✅ Conforme | Versión final estable de la rama Laravel 10 LTS. |
| **Catálogo de Modelos** | *"45 Modelos"* (Sección 5.2 del README) | **46 Modelos** reales en `app/Models/` | ⚠️ Discrepancia menor | El título del README indica 45, pero la tabla enumera 46 modelos (del 1 al 46). El conteo físico en disco es exactamente 46 archivos `.php`. |
| **Migraciones** | *"63 migraciones estructuradas"* | **63 archivos** en `database/migrations/` | ✅ Conforme | Conteo exacto verificado contra el sistema de archivos. |
| **Rutas Registradas** | *"280+ rutas web"* | **292 rutas** en `routes/web.php` + 4 en `routes/api.php` | ✅ Conforme | Cobertura total de endpoints documentados. |

### Inventario de los 46 Modelos Existentes en `app/Models/`:
`ArchingCash`, `Billing`, `Business`, `Buy`, `Cash`, `Category`, `Client`, `CocktailMenuCategory`, `CocktailMenuItem`, `Contract`, `ContractClause`, `ContractInstallment`, `ContractItem`, `Country`, `CreditNoteType`, `Currency`, `DebitNoteType`, `Department`, `DetailBilling`, `DetailBuy`, `DetailPayment`, `DetailQuote`, `DetailSaleNote`, `DetailTransferOrder`, `District`, `EventChecklist`, `EventChecklistItem`, `Expense`, `IdentityDocumentType`, `IgvTypeAffection`, `PayMode`, `Product`, `ProductPresentation`, `Provider`, `Province`, `Quote`, `SaleNote`, `Serie`, `ShipmentGuide`, `ShipmentGuideItem`, `StockProduct`, `TransferOrder`, `TypeDocument`, `Unit`, `User`, `Warehouse`.

---

## 3. Matriz de Módulos: PDR Sección 8 y PRD Legado (Ítem 1)

**Criterios de Evaluación:**
- ✅ **Existe**: Controlador, modelo, rutas con protección, vistas y lógica funcional completa.
- ⚠️ **Parcial**: El módulo existe y opera, pero presenta omisiones lógicas, falta de transaccionalidad, filtros incompletos o desprotección parcial.
- ❌ **Faltante**: No implementado o inexistente en el codebase.

### 3.1. Evaluación de los Módulos de la Sección 8 del PDR

| # | Módulo / Funcionalidad | Controlador | Modelos Principales | Rutas (`web.php` / `api.php`) | Vistas Blade | Estado | Observaciones Técnicas y Brechas |
| :-: | :--- | :--- | :--- | :--- | :--- | :-: | :--- |
| 1 | **Autenticación y Sesión** | `LoginController` | `User` | `/`, `/login/*` | `login.blade.php` | ✅ Existe | Flujo completo de login, logout y redirección por sesión. |
| 2 | **Panel Principal y Métricas** | `HomeController` | `Billing`, `SaleNote`, `DetailPayment` | `/home/*` | `admin.home` | ⚠️ Parcial | Funcional, pero repite consultas agregadas idénticas en memoria (`sum('stock_actual')` x2) y calcula presupuesto con meta fija en duro (100,000). |
| 3 | **Conmutador de Almacén** | `WarehouseSelectorController` | `Warehouse`, `User` | `/establishment/*` | `establishment.blade.php` | ✅ Existe | Conmuta almacén activo en sesión y en base de datos. Solo `auth` (intencional para todo usuario). |
| 4 | **Configuración de Empresa** | `BusinessController` | `Business`, `Country`, `Ubigeo` | `/business/*` | `admin.business.home` | ✅ Existe | Carga de logo, datos fiscales y credenciales SOL/GRE. |
| 5 | **Control de Cajas Físicas** | `CashController` | `Cash` | `/cashes/*` | `admin.cashes.list` | ✅ Existe | CRUD completo de cajas con permisos `can:admin.cashes`. |
| 6 | **Medios de Pago** | `PayModeController` | `PayMode` | `/pay-modes/*` | `admin.paymodes.list` | ✅ Existe | CRUD y mapeo con catálogo SUNAT. |
| 7 | **Series y Correlativos** | `SerieController` | `Serie`, `TypeDocument`, `Cash` | `/series/*` | `admin.series.list` | ✅ Existe | Mantenimiento de series segregadas por caja. |
| 8 | **Países y Monedas** | `CountryController` | `Country` | `/countries/*` | `admin.countries.list` | ⚠️ Parcial | **Falla de Seguridad**: Solo `GET /` tiene middleware `auth`. Los endpoints `/save`, `/detail`, `/store`, `/delete` están desprotegidos. |
| 9 | **Clientes y Padrón Fiscal** | `ClientController` | `Client`, `IdentityDocumentType` | `/clients/*` | `admin.clients.list` | ✅ Existe | Búsqueda por DNI/RUC con Factiliza API y validaciones. |
| 10 | **Proveedores de Insumos** | `ProviderController` | `Provider`, `Client` | `/providers/*` | `admin.providers.list` | ✅ Existe | Soporte fiscal idéntico a clientes para abastecimiento. |
| 11 | **Categorías de Productos** | `CategoryController` | `Category` | `/categories/*` | `admin.categories.list` | ✅ Existe | CRUD y vinculación al catálogo. |
| 12 | **Productos y Presentaciones** | `ProductController` | `Product`, `ProductPresentation`, `StockProduct` | `/products/*` | `admin.products.list`, modales | ✅ Existe | Separación de producto vs. servicio, alquiler, presentaciones y código de barras. |
| 13 | **Almacenes y Stock Físico** | `WarehouseController` | `Warehouse`, `StockProduct` | `/warehouses/*` | `admin.warehouses.list`, `products.list` | ⚠️ Parcial | `barcode_sum` y ajustes directos no usan transacciones ni `lockForUpdate`. `list_products` carga toda la tabla en RAM antes de DataTables. |
| 14 | **Kardex Multi-Almacén** | `KardexController` | `StockProduct`, `Product`, movimientos | `/kardex/*` | `admin.kardex.index` | ✅ Existe | Trazabilidad cronológica de entradas y salidas con saldo continuo. |
| 15 | **Compras y Abastecimiento** | `BuyController` | `Buy`, `DetailBuy`, `StockProduct` | `/buys/*` | `admin.buys.list`, `create`, `pdf` | ⚠️ Parcial | Transacción activa, pero actualiza stock con casteo forzado a `(int)`, truncando decimales. No usa `lockForUpdate()`. |
| 16 | **Cotizaciones y Proformas** | `QuoteController` | `Quote`, `DetailQuote`, `Client` | `/quotes/*` | `admin.quotes.list`, `create`, `edit`, `pdf` | ⚠️ Parcial | Conversión a venta (`convert_to_sale`) no marca la cotización como consumida/convertida (permite multi-conversión). Correlativo sin bloqueo. |
| 17 | **Contratos de Servicios y Cuotas** | `ContractController` | `Contract`, `ContractItem`, `ContractInstallment`, `ContractClause` | `/contracts/*` | `admin.contracts.list`, `create`, `edit`, `pdf` | ⚠️ Parcial | Módulo muy completo (Canvas firma, cuotas, PDF A4). Sin embargo, no existe estado `Borrador/Preliminar` para pedidos públicos (B2), y correlativo carece de bloqueo pesimista. |
| 18 | **Checklists Operativos Eventos** | `EventChecklistController` | `EventChecklist`, `EventChecklistItem` | `/event-checklists/*` | `admin.event_checklists.index`, `tablet`, `pdf` | ⚠️ Parcial | Excelente interfaz táctil para Tablets. **Brecha Crítica**: Las devoluciones con daño/pérdida no descuentan ni reintegran stock; las penalidades son solo informativas y no generan cobro ni cuota. Reutiliza permiso de contratos. |
| 19 | **Menú de Cócteles y Carta Touch** | `CocktailMenuController` | `CocktailMenuItem`, `CocktailMenuCategory` | `/cocktail-menu/*` | `admin.cocktail_menu.index`, `tablet` | ⚠️ Parcial | Vista interactiva con ficha técnica y switch de precios. Carece de catálogo público para clientes (roadmap B1) y reutiliza permiso de contratos. |
| 20 | **Punto de Venta Táctil (POS)** | `PosController` | `Billing`, `SaleNote`, `DetailPayment`, `StockProduct`, `Serie` | `/pos/*` | `admin.pos.list`, `home`, tickets 80mm | ⚠️ Parcial | Soporta ítems custom, descuentos y múltiples pagos. **Riesgo**: La reserva del correlativo de la serie se realiza fuera de la transacción y sin `lockForUpdate()`. Verificación de caja no valida almacén activo. |
| 21 | **Notas de Venta Internas** | `SaleNoteController` | `SaleNote`, `DetailSaleNote`, `StockProduct` | `/salenotes/*` | `admin.sale_notes.list`, `pdf` | ⚠️ Parcial | Anulación (`anulled`) no está en transacción, no usa lock, castea a `intval` borrando decimales, y reutiliza erróneamente variable `$product["id"]`. |
| 22 | **Facturación Electrónica UBL 2.1** | `BillingController` | `Billing`, `DetailBilling`, `CreditNoteType` | `/billings/*` | `admin.billings.list`, `credit_notes`, `debit_notes` | ⚠️ Parcial | Emisión, firma XML-DSig y CDR operativos. **Brechas**: Las Notas de Crédito de anulación total NO restituyen inventario a `stock_products`. No valida límite de 72 horas. |
| 23 | **Guías de Remisión GRE** | `ShipmentGuideController` | `ShipmentGuide`, `ShipmentGuideItem` | `/shipment-guides/*` | `admin.shipment_guides.list`, `create`, `pdf` | ⚠️ Parcial | Estructura base para GRE Remitente, pero desconectada de traslados internos o eventos/contratos (roadmap B5). |
| 24 | **Traslados entre Almacenes** | `TransferOrderController` | `TransferOrder`, `DetailTransferOrder`, `StockProduct` | `/transferorders/*` | `admin.transfer_orders.list`, `create`, `pdf` | ⚠️ Parcial | El método `move` (confirmar recepción) actualiza existencias sin `DB::transaction`, sin `lockForUpdate()` y truncando stock decimal con `(int)`. |
| 25 | **Arqueo y Cierre de Caja** | `ArchingCashController` | `ArchingCash`, `Cash`, `DetailPayment` | `/archingcash/*` | `admin.arching_cashes.list`, `ticket` | ⚠️ Parcial | Apertura, depósitos, retiros y resumen. En `detail_cashes` itera colecciones con consultas implícitas (N+1 potencial). |
| 26 | **Cierre Diario de Caja y Gastos** | `DailyCashClosingController` | `Expense`, `ArchingCash`, `Billing`, `SaleNote` | `/archingcash/cierre-diario/*` | `admin.arching_cashes.daily_closing`, tickets | ✅ Existe | Conciliación de ventas del día mediante UNION limpia, registro de gastos operativos menores y ticket térmico de liquidación. |
| 27 | **Usuarios del Sistema** | `UserController` | `User`, `Cash`, `Warehouse` | `/users/*` | `admin.users.list` | ✅ Existe | Gestión de usuarios, asignación de caja y almacén predeterminado. |
| 28 | **Roles y Permisos Spatie** | `RoleController` | `Role`, `Permission` (Spatie) | `/roles/*` | `admin.roles.list` | ✅ Existe | Administración de roles del sistema (`SUPERADMIN`, `ADMIN`, `VENDEDOR`, `CAJERO`, `ALMACENERO`). |
| 29 | **Reportes Tributarios CPE** | `BillingReportController` | `Billing`, `DetailBilling`, `TypeDocument` | `/billings/reports/*` | `admin.reports.billings.*`, Excel, PDF | ✅ Existe | Registro de ventas oficial SUNAT, estado de comprobantes y notas de crédito con base gravada, IGV y total. |
| 30 | **Reportes Comerciales y Ventas** | `ReportSalesController` | `SaleNote`, `DetailSaleNote`, `Product` | `/reportes/ventas/*`, `/by-product` | `admin.reports.sales.*` | ⚠️ Parcial | **Defecto Grave**: Solo consulta `SaleNote`. Omite completamente todas las Facturas y Boletas (`billings`). |
| 31 | **Reportes por Medios de Pago** | `ReportPaymentController` | `DetailPayment`, `PayMode`, `SaleNote` | `/reportes/pagos/*` | `admin.reports.payments.*` | ⚠️ Parcial | **Defecto Grave**: Une `SaleNote` con `detail_payments` sin filtrar por tipo de comprobante ni incluir `billings`. |
| 32 | **API REST SUNAT Dispatch** | `SunatValidationController`, `SunatBillingPayloadController`, `SunatDispatchController` | `Billing`, `Business` | `/api/sunat/*` | JSON Endpoints | ⚠️ Parcial | **Falla Crítica de Seguridad**: Endpoints públicos sin middleware de autenticación (`auth:sanctum`), expuestos a extracción de datos y envíos no autorizados. |

---

### 3.2. Evaluación de los 9 Módulos del PRD Legado (`docs/PRD_MODULOS_PENDIENTES_FE.md`)

| # | Módulo Legado PRD | Controlador Responsable | Estado Actual | Diagnóstico de Adecuación Operativa |
| :-: | :--- | :--- | :-: | :--- |
| 1 | **Productos por Almacén** | `WarehouseController` | ⚠️ Parcial | Separación producto/servicio operativa. Persisten problemas de atomicidad en ajustes rápidos y truncamiento de stock fraccionario en traslados y compras. |
| 2 | **Proveedores** | `ProviderController` | ✅ Resuelto | Implementado formalmente con tipos de documento SUNAT, consulta API y validación anti-duplicados. |
| 3 | **Compras** | `BuyController` | ⚠️ Parcial | Flujo operativo con almacén destino y validación anti-duplicados por serie/correlativo. Requiere corregir casteo `(int)` y verificar que no admita ítems tipo servicio como stock físico. |
| 4 | **POS** | `PosController` | ⚠️ Parcial | Convivencia de Boleta, Factura y Nota de Venta lograda. Falta asegurar la reserva del correlativo bajo bloqueo pesimista. |
| 5 | **Comprobantes** | `BillingController` | ⚠️ Parcial | Listados, XML, CDR, PDF y notas de crédito/débito operativos. Falta incorporar la restitución automática de existencias en notas de crédito de anulación total. |
| 6 | **Notas de Venta** | `SaleNoteController` | ⚠️ Parcial | Flujo de venta interna separado exitosamente. El método de anulación requiere refactorización urgente para garantizar integridad transaccional del inventario. |
| 7 | **Arqueo de Cajas** | `ArchingCashController` / `DailyCashClosingController` | ✅ Resuelto | El módulo de Cierre Diario consolidó con éxito la conciliación de ingresos reales por medio de pago y egresos por gastos menores (`expenses`). |
| 8 | **Cotizaciones** | `QuoteController` | ⚠️ Parcial | Cotizaciones funcionales y convertibles al POS. Debe agregarse el flag de estado `convertida` para evitar múltiples ventas del mismo presupuesto. |
| 9 | **Reportes** | `BillingReportController` / `ReportSalesController` | ⚠️ Parcial | El reporte tributario (`BillingReportController`) es impecable. Los reportes analíticos (`ReportSalesController`, `ReportPaymentController`) están truncados porque no consolidan facturación electrónica. |

---

## 4. Auditoría de las 7 Reglas de Negocio Críticas (PDR Sección 6) (Ítem 2)

Evaluación línea por línea de la implementación de las reglas definidas en la sección 6 del PDR:

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                AUDITORÍA DE REGLAS DE NEGOCIO                                    │
├────┬───────────────────────────────────────┬────────────────┬────────────────────────────────────┤
│ N° │ Regla de Negocio                      │ Estado Código  │ Implementado en vs. Brechas        │
├────┼───────────────────────────────────────┼────────────────┼────────────────────────────────────┤
│ 1  │ Invariabilidad de Stock por Servicios │ ⚠️ Parcial     │ POS: OK / Compras & Checklists: NO │
│ 2  │ Exclusividad de Caja Abierta          │ ⚠️ Parcial     │ POS: OK / Validación Almacén: NO   │
│ 3  │ Identidad Fiscal Facturas (RUC 11 d.) │ ✅ Conforme    │ PosController: Líneas 740 y 1390   │
│ 4  │ Anulación Comprobantes & Restitución  │ ❌ No Conforme │ NC no restituye stock / NV insegura│
│ 5  │ Autonumeración CON-* y CHK-*          │ ⚠️ Parcial     │ Formato OK / Sin Bloqueo Race Cond.│
│ 6  │ Integridad de Cuotas (Suma == Total)  │ ✅ Conforme    │ ContractController: 215 y 392      │
│ 7  │ Menaje, Daños y Penalidades           │ ⚠️ Parcial     │ Checklist: OK / Impacto Fiscal: NO │
└────┴───────────────────────────────────────┴────────────────┴────────────────────────────────────┘
```

### Detalle Específico por Regla:

#### Regla 1: Invariabilidad del Stock por Servicios (`opcion = 2`)
* **Dónde SÍ está implementada**:
  - `app/Http/Controllers/PosController.php:1520-1522` (`validateStockBeforeSale`): Omite validación de existencias si `(int) $product['opcion'] === 2`.
  - `app/Http/Controllers/PosController.php:1251`: En el loop de descuento de stock de `save_sale`, omite el decremento en `StockProduct` para servicios.
  - `app/Http/Controllers/SaleNoteController.php:333`: En la anulación de notas de venta, verifica `$product->opcion == 1` antes de reingresar inventario.
  - `app/Http/Controllers/WarehouseController.php:547-553`: En `barcode_sum`, rechaza incremento de stock si el producto es servicio.
  - `app/Http/Controllers/EventChecklistController.php:142`: Omite servicios intangibles al importar ítems del contrato al checklist.
* **Dónde NO está implementada (Brechas)**:
  - `app/Http/Controllers/BuyController.php:206-220`: Al registrar una compra, no discrimina si el ítem comprado es servicio (`opcion = 2`). Si se registra una compra de un servicio que no existe en `StockProduct`, arroja `RuntimeException` o intenta actualizar stock inexistente.
  - `app/Http/Controllers/TransferOrderController.php:175-210`: No valida si un ítem en traslado es intangible.

#### Regla 2: Exclusividad de Caja Abierta para Ventas
* **Dónde SÍ está implementada**:
  - `app/Http/Controllers/PosController.php:66-76`: En `create`, verifica `ArchingCash::where('idcaja', $idcaja)->where('idusuario', $idusuario)->where('estado', 1)`. Si no hay caja abierta, bloquea la vista y redirige con advertencia.
  - `app/Http/Controllers/PosController.php:767-779`: En `open_modal`, valida existencia de arqueo activo antes de procesar el pago.
  - `app/Http/Controllers/PosController.php:1142-1154`: En `save_sale`, valida arqueo activo previo a ejecutar la transacción.
* **Dónde NO está implementada (Brechas)**:
  - Las consultas en `PosController` verifican `idcaja` e `idusuario`, pero **no validan que `idalmacen` del arqueo coincida con el almacén activo en sesión** (`selected_warehouse_id`). Si un usuario cambió de almacén manteniendo una caja abierta en otra sede, el sistema permite vender con la caja cruzada.

#### Regla 3: Validación de Identidad Fiscal para Facturas (RUC 6 de 11 dígitos)
* **Dónde SÍ está implementada**:
  - `app/Http/Controllers/PosController.php:738-746` (en `open_modal`):
    ```php
    $client = Client::with('tipoDocumento')->find((int) $data['client_id']);
    $clientDocumentCode = trim((string) optional($client?->tipoDocumento)->codigo);
    if ((string) $documentType->codigo === '01' && $clientDocumentCode !== '6') {
        return response()->json(['status' => false, 'msg' => 'Para emitir factura, el cliente debe tener RUC.'], 422);
    }
    ```
  - `app/Http/Controllers/PosController.php:1390-1392` (`validateClientDocumentForSale`): Exige código `'6'` y expresión regular exacta de 11 dígitos numéricos `preg_match('/^\d{11}$/', $documentNumber)`.
* **Dónde NO está implementada**:
  - En `QuoteController.php:556` (`convert_to_sale`): No valida el tipo de documento del cliente antes de preparar la conversión.

#### Regla 4: Anulaciones de Comprobantes y Restitución de Stock
* **Dónde SÍ está implementada**:
  - `app/Http/Controllers/BillingController.php:407-472`: Permite emitir Nota de Crédito Electrónica tipo `01` (Anulación de la operación) o tipo `02` sobre facturas y boletas aceptadas por SUNAT.
  - `app/Http/Controllers/SaleNoteController.php:310-354`: Anula la Nota de Venta cambiando estado a `2` e incrementa el stock.
* **Dónde NO está implementada / Defectos Críticos**:
  - **Falta Total de Restitución en Facturación Electrónica**: En `BillingController.php:923-998` (`storeCreditNoteFromBilling`), cuando SUNAT acepta la nota de crédito de anulación, **NO existe una sola línea de código que sume el stock de vuelta en `stock_products`**. La clase `StockProduct` ni siquiera está importada en el controlador.
  - **Ausencia de Regla de 72 Horas**: `BillingController` no verifica la antigüedad de `fecha_emision` del comprobante para restringir la anulación a las 72 horas normativas de SUNAT.
  - **Defectos en Anulación de Notas de Venta** (`SaleNoteController.php:327-347`):
    - Se ejecuta sin `DB::transaction()`.
    - No utiliza `lockForUpdate()`.
    - Realiza `intval($product["cantidad"])`, destruyendo cantidades decimales.
    - Error de sintaxis lógica en línea 335: sobreescribe la variable `$product` con el modelo `Product`, intentando actualizar `StockProduct` con `$product["id"]`.
    - Cualquier usuario con permiso de lectura (`admin.sale_notes`) puede anular notas sin validación de rol supervisor.

#### Regla 5: Autonumeración de Contratos y Checklists
* **Dónde SÍ está implementada**:
  - `app/Http/Controllers/ContractController.php:737-751` (`generateNextContractNumber`): Genera el patrón `CON-{AÑO}-{CORRELATIVO_4_DIGITOS}` (ej. `CON-2026-0001`).
  - `app/Http/Controllers/EventChecklistController.php:481-494` y `app/Models/EventChecklist.php:159-172` (`generateNextCode`): Genera el patrón `CHK-{AÑO}-{CORRELATIVO_4_DIGITOS}` (ej. `CHK-2026-0001`).
* **Dónde NO está implementada (Brecha de Integridad)**:
  - Ambas funciones leen el último registro con `orderBy('id', 'desc')->first()` sin bloqueo pesimista ni transacción atómica. Si dos operadores registran un contrato o checklist simultáneamente, ambos recibirán el mismo correlativo, ocasionando un error de duplicidad de clave única en base de datos.

#### Regla 6: Integridad de Cuotas (Suma de Cuotas == Total del Contrato)
* **Dónde SÍ está implementada**:
  - `app/Http/Controllers/ContractController.php:213-222` (en `store`):
    ```php
    $sumInstallments = round((float) collect($installmentsData)->sum(fn($i) => floatval($i['monto'] ?? 0)), 2);
    if (abs($sumInstallments - $total) > 0.05) {
        return response()->json(['status' => false, 'msg' => 'La suma de las cuotas (...) debe coincidir con el total...'], 422);
    }
    ```
  - `app/Http/Controllers/ContractController.php:389-399` (en `update`): Valida exactamente la misma regla para modificaciones.
  - `app/Http/Controllers/PosController.php:1115-1123`: Valida que la suma de cuotas en ventas al crédito en el POS sea igual al total de la venta.
* **Dónde NO está implementada**:
  - `ContractController@pay_installment`: Permite pagar cuotas pero no restringe sobrepagos que superen el saldo pendiente del contrato.

#### Regla 7: Control de Menaje y Penalidades Post-Evento
* **Dónde SÍ está implementada**:
  - `app/Models/EventChecklistItem.php:109-131` (`reportIncident`): Registra la bandera `tiene_incidencia = true`, el tipo (`danado`, `faltante`, `quebrado`, `consumido`), observaciones y el campo `costo_penalidad_estimado`.
  - `app/Http/Controllers/EventChecklistController.php:230-238` (`updateItem`): Permite al supervisor en Tablet registrar incidencias y costos estimados de penalidad en tiempo real.
  - `EventChecklistController@pdfReport`: Imprime el resumen detallado de incidencias y montos de penalidad en el Acta PDF.
* **Dónde NO está implementada (Brecha Operativa y Fiscal)**:
  - El costo de penalidad no se autocompleta con el costo de reposición (`precio_compra`) del catálogo si el usuario no lo digita manualmente.
  - La penalidad es **estrictamente informativa**: No crea una cuota adicional en el contrato, no genera una cuenta por cobrar ni emite una Nota de Débito o Comprobante en caja.

---

## 5. Análisis de Riesgos de Integridad del Código (Ítem 3)

### (a) Deducciones y Movimientos de Stock sin `DB::transaction` o sin `lockForUpdate`

| Controlador / Método | Archivo y Líneas | ¿Usa `DB::transaction`? | ¿Usa `lockForUpdate`? | Nivel de Riesgo | Detalle del Riesgo de Integridad |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `PosController@save_sale` | `PosController.php:1173, 1526` | ✅ Sí | ✅ Sí | **Bajo** | Implementación correcta: bloquea `StockProduct` dentro de transacción antes de descontar. |
| `BuyController@save` | `BuyController.php:171, 206-219` | ✅ Sí | ❌ No | **Medio** | Actualiza `stock_actual` dentro de transacción pero sin bloqueo pesimista. Realiza casteo entero `(int)` destruyendo decimales. |
| `SaleNoteController@anulled` | `SaleNoteController.php:327-347` | ❌ No | ❌ No | 🔴 **Alto** | Reintegra existencias sin transacción ni bloqueo. Sobreescritura de variable `$product` provoca inconsistencias. |
| `TransferOrderController@move` | `TransferOrderController.php:175-210` | ❌ No | ❌ No | 🔴 **Alto** | Transfiere stock entre almacenes en un `foreach` abierto sin transacción ni lock. Condición de carrera inmediata ante múltiples traslados. |
| `WarehouseController@barcode_sum` | `WarehouseController.php:555-559` | ❌ No | ❌ No | 🟡 **Medio** | Incremento directo por lector de barras sin bloqueo pesimista. |
| `WarehouseController@sum_stock` | `WarehouseController.php:480-500` | ❌ No | ❌ No | 🟡 **Medio** | Ajuste manual de stock sin bloqueo transaccional. |

---

### (b) Riesgo de Doble Deducción entre `sale_notes` y `billings`

1. **En Punto de Venta (POS)**: En el flujo estándar de `PosController@save_sale`, la bifurcación es excluyente: si el documento es tipo `02` se crea una `SaleNote`, de lo contrario se crea un `Billing`. No existe doble descuento en una sola operación POS.
2. **En Conversión de Cotizaciones (`QuoteController@convert_to_sale`)**:
   - `QuoteController.php:556-585` transfiere los ítems cotizados a `session('pos')` y redirige al POS.
   - **Vulnerabilidad**: La cotización **no cambia de estado** (permanece activa). El vendedor puede abrir el enlace de conversión múltiples veces o distintos vendedores pueden convertir la misma cotización, cobrándola y descontando el stock dos o más veces sin que el sistema lo impida.
3. **Facturación Posterior de Notas de Venta**: Si un cliente solicita formalizar una Nota de Venta previa en Factura electrónica, el sistema actual no cuenta con un conversor directo (`convert_salenote_to_billing`). Si el vendedor vuelve a ingresar los productos en el POS como Factura, el inventario se descontará doblemente.

---

### (c) Numeración Secuencial (Correlativos) sin Bloqueo de Concurrencia

| Entidad / Documento | Archivo y Líneas | Mecanismo de Obtención | ¿Usa `lockForUpdate`? | Nivel de Riesgo | Consecuencia Técnica |
| :--- | :--- | :--- | :---: | :---: | :--- |
| **Series POS (Facturas/Boletas/NV)** | `PosController.php:1156-1170` | `$serieModel = Serie::where(...)->first();` | ❌ No (se lee antes de la transacción) | 🔴 **Alto** | Si dos cajeros cobran simultáneamente en la misma caja/serie, ambos leen el mismo correlativo antes del commit, generando colisión de número en SUNAT o error de llave duplicada. |
| **Contratos** | `ContractController.php:737-751` | `Contract::where(...)->orderBy('id','desc')->first()` | ❌ No | 🟡 **Medio** | Riesgo de colisión de `contract_number` ante suscripción simultánea de dos contratos. |
| **Checklists** | `EventChecklistController.php:481-494` | `EventChecklist::where(...)->orderBy('id','desc')->first()` | ❌ No | 🟡 **Medio** | Riesgo de colisión de código `CHK-YYYY-XXXX`. |
| **Cotizaciones** | `QuoteController.php:398-399` | `Quote::latest('id')->first()["correlativo"]` | ❌ No | 🟡 **Medio** | Obtención secuencial sin bloqueo pesimista. |
| **Notas de Crédito** | `BillingController.php:1000-1010` | `Serie::where(...)->lockForUpdate()->first()` | ✅ Sí | **Bajo** | Implementación correcta y protegida con bloqueo pesimista en base de datos. |

---

### (d) Rutas sin Middleware de Autenticación o Permisos

1. **Vulnerabilidad Crítica en Países (`routes/web.php:99-105`)**:
   ```php
   Route::controller(CountryController::class)->prefix('countries')->group(function() {
       Route::get('/'      , 'index')->name('admin.countries')->middleware('auth');
       Route::get('/get'   , 'get')->name('countries.get');
       Route::post('/save'  , 'save')->name('countries.save');
       Route::post('/detail', 'detail')->name('countries.detail');
       Route::post('/store' , 'store')->name('countries.store');
       Route::post('/delete', 'delete')->name('countries.delete');
   });
   ```
   **Diagnóstico**: El grupo no tiene middleware. Solo `index` requiere `auth`. Las peticiones POST a `/countries/save`, `/store` y `/delete` pueden ser ejecutadas por **usuarios no autenticados (invitados / anónimos)**.
2. **Exposición Pública de API SUNAT (`routes/api.php:24-26`)**:
   ```php
   Route::post('/sunat/validate', SunatValidationController::class);
   Route::get('/sunat/billings/{billing}/payload', SunatBillingPayloadController::class);
   Route::post('/sunat/billings/{billing}/dispatch', SunatDispatchController::class);
   ```
   **Diagnóstico**: No cuentan con `auth:sanctum` ni middleware de permisos. Cualquier cliente en la red puede consultar el payload completo con datos tributarios del cliente (`/payload`) o forzar timbrados ante SUNAT (`/dispatch`).
3. **Reutilización Impropia de Permisos Spatie**:
   - `EventChecklistController` (`routes/web.php:240`) utiliza `middleware(['auth', 'can:admin.contracts'])`.
   - `CocktailMenuController` (`routes/web.php:253`) utiliza `middleware(['auth', 'can:admin.contracts'])`.
   - No existen los permisos independientes `admin.event_checklists` ni `admin.cocktail_menu`. Un usuario con acceso a contratos adquiere automáticamente control total sobre los checklists y la carta de barra.

---

### (e) Problemas de Rendimiento y Consultas N+1 en Listados

1. **Recorrido con Consultas Implícitas en Arqueo de Caja (`ArchingCashController.php:446-485`)**:
   - En `get_summary` y `detail_cashes`, se itera sobre colecciones de `SaleNote` y `Billing` accediendo dinámicamente a `$billing->payMode->descripcion` cuando el breakdown está vacío, ejecutando consultas SQL adicionales por cada comprobante dentro del bucle si no fue pre-cargado con eager loading.
2. **Carga Innecesaria de Tablas Completas en Memoria antes de DataTables**:
   - `WarehouseController.php:274`: `$productos = $query->orderByDesc(...)->get(); return datatables()->of($productos);`.
   - `WarehouseController.php:407`: `$productos = $productos->orderByDesc(...)->get(); return datatables()->of($productos);`.
   - Al ejecutar `->get()`, se cargan todos los registros del inventario en la memoria RAM de PHP en lugar de delegar el `LIMIT` y `OFFSET` al motor MySQL a través del Query Builder.
3. **Omisión de Facturación en Analítica Comercial**:
   - `ReportSalesController.php:22` y `ReportPaymentController.php:20`: Los listados y gráficos de reportes de ventas omiten el 100% de los comprobantes electrónicos (`billings`), entregando reportes parciales e inconsistentes con el Registro de Ventas tributario.

---

## 6. Auditoría de Columnas de Estado y Enums en Tablas Clave (Ítem 4)

Estructura real en base de datos obtenida directamente de las migraciones y modelos:

### 6.1. Tabla `contracts`
* **Columnas Reales**:
  `id`, `contract_number`, `title`, `idcliente`, `provider_name`, `provider_document`, `provider_representative`, `fecha_evento`, `hora_evento`, `lugar_evento`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `igv`, `total`, `cuotas` (JSON), `moneda`, `observaciones`, `firma_cliente`, `firma_proveedor`, `estado`, `idusuario`, `idalmacen`, `created_at`, `updated_at`.
* **¿Existe columna de estado?**: **SÍ**, la columna se llama `estado` (`tinyInteger`, default 1).
* **Constantes en Modelo `Contract.php`**:
  - `STATUS_DRAFT = 0` (Borrador)
  - `STATUS_SIGNED = 1` (Firmado / Activo)
  - `STATUS_COMPLETED = 2` (Completado)
  - `STATUS_VOIDED = 3` (Anulado)
  - *Nota*: **No existe** `STATUS_PRELIMINARY` (Requerido para la fase B2 de pedidos web y pre-contratos).

### 6.2. Tabla `contract_installments`
* **Columnas Reales**:
  `id`, `contract_id`, `numero_cuota`, `descripcion`, `monto`, `porcentaje`, `fecha_vencimiento`, `fecha_pago`, `estado`, `metodo_pago`, `referencia_pago`, `observaciones`, `created_at`, `updated_at`.
* **Columna de Estado**: `estado` (`tinyInteger`, default 0).
* **Constantes en Modelo `ContractInstallment.php`**:
  - `STATUS_PENDING = 0` (Pendiente de pago)
  - `STATUS_PAID = 1` (Pagado / Amortizado)

### 6.3. Tabla `event_checklists`
* **Columnas Reales**:
  `id`, `contract_id`, `codigo`, `titulo`, `fecha_evento`, `hora_evento`, `lugar_evento`, `responsable_montaje`, `responsable_desmontaje`, `estado`, `total_items`, `items_llevados`, `items_devueltos`, `items_con_incidencia`, `observaciones_salida`, `observaciones_retorno`, `idusuario`, `idalmacen`, `created_at`, `updated_at`.
* **Columna de Estado**: `estado` (`tinyInteger`, default 0).
* **Enums / Constantes en Modelo `EventChecklist.php`**:
  - `STATUS_PLANNED = 0` (Planificado / Borrador)
  - `STATUS_DISPATCHED = 1` (En Montaje / Despachado)
  - `STATUS_RETURNED_OK = 2` (Conforme / Retornado sin daños)
  - `STATUS_WITH_INCIDENTS = 3` (Con Incidencias / Daños o Faltantes)

### 6.4. Tabla `event_checklist_items`
* **Columnas Reales**:
  `id`, `checklist_id`, `contract_item_id`, `idproducto`, `descripcion`, `categoria`, `cantidad_planeada`, `cantidad_llevada`, `llevado` (boolean), `idusuario_llevado`, `fecha_llevado`, `cantidad_devuelta`, `devuelto` (boolean), `idusuario_devuelto`, `fecha_devuelto`, `tiene_incidencia` (boolean), `tipo_incidencia` (string 50), `observaciones`, `costo_penalidad_estimado`, `orden`, `created_at`, `updated_at`.
* **Control de Estado de Ítem**: Representado por tres banderas booleanas (`llevado`, `devuelto`, `tiene_incidencia`).
* **Valores Permitidos para `tipo_incidencia`**:
  - En comentario de migración: `roto`, `extraviado`, `danado`, `incompleto`.
  - En interfaz Tablet y Controlador (`EventChecklistController.php` y `tablet.blade.php`): `'danado'`, `'faltante'`, `'quebrado'`, `'consumido'`, `'incompleto'`.

### 6.5. Tabla `cocktail_menu_items`
* **Columnas Reales**:
  `id`, `product_id`, `menu_category_id`, `nombre`, `descripcion_corta`, `cristaleria`, `garnish`, `imagen`, `precio`, `es_autor` (boolean), `destacado` (boolean), `orden`, `activo` (boolean), `created_at`, `updated_at`.
* **Control de Estado**: Columna `activo` (`boolean`, default true).
* **Atributos de Clasificación**: `es_autor` (boolean) y `destacado` (boolean).

---

## 7. Verificación de Datos Tributarios y Credenciales Hardcodeadas (Ítem 5)

Inspección de cadenas de texto de credenciales, RUCs y razones sociales fuera de seeders de prueba:

| Archivo | Ubicación | Tipo de Dato | Valor Encontrado | Nivel de Severidad | Diagnóstico y Acción Requerida |
| :--- | :--- | :--- | :--- | :---: | :--- |
| `app/Http/Controllers/Controller.php` | Línea 27 | **Token JWT Bearer API** | `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...` (Subject: `Mytems`, Correo: `mytemscontacto@gmail.com`) | 🔴 **Alta** | Token de producción/servicio para Factiliza API hardcodeado en controlador base. Debe migrarse a `.env` (`FACTILIZA_TOKEN`). |
| `resources/views/admin/contracts/pdf.blade.php` | Líneas 231-232 | **Ruta de Logo en Disco** | `public_path('files/logos/hardys-peru-investments-sac-20260921003702.png')` | 🟡 **Media** | Nombre de archivo específico de empresa previa como fallback. Debe resolverse dinámicamente desde `$business->logo`. |
| `resources/views/admin/contracts/pdf.blade.php` | Líneas 243-247 | **Datos Fiscales Proveedor** | RUC: `20612900591`, Empresa: `Hardys Perú Investments S.A.C.`, Rep: `Harry Salazar García`, Doc: `46784193`, Dirección: Tarapoto | 🟡 **Media** | Valores por defecto estáticos en la plantilla PDF si no se cargan datos de empresa. Deben tomarse 100% de la tabla `businesses`. |
| `resources/views/admin/contracts/pdf.blade.php` | Líneas 249-251 | **Datos Fiscales Cliente** | RUC: `20610984666`, Razón Social: `Suiza Village S.A.C.` | 🟡 **Media** | Fallback hardcodeado para cliente en contratos. Debe lanzar excepción o exigir cliente válido. |
| `database/seeders/BusineSeeder.php` | Líneas 13-34 | Datos de Seeder Inicial | RUC `20610316884`, usuario SOL `MYTEMS23`, clave cert `mytems2022` | 🟢 **Baja** | Conforme: Se encuentra dentro de la carpeta `database/seeders/` destinada a inicialización de base de datos. |

---

## 8. Matriz Consolidada de Riesgos y Severidad

Resumen de vulnerabilidades técnicas e inconsistencias operativas detectadas:

| ID | Área / Componente | Descripción del Hallazgo | Severidad | Impacto en el Sistema |
| :-: | :--- | :--- | :---: | :--- |
| **SEC-01** | `routes/web.php` | Rutas de `CountryController` (`/save`, `/store`, `/delete`) sin middleware `auth`. | 🔴 **ALTA** | Modificación o eliminación no autorizada de monedas y países por usuarios anónimos. |
| **SEC-02** | `routes/api.php` | Endpoints `/api/sunat/*` (validate, payload, dispatch) públicos y sin autenticación. | 🔴 **ALTA** | Fuga de datos de facturación y capacidad de forzar timbrados no autorizados ante SUNAT. |
| **SEC-03** | `Controller.php:27` | Token JWT privado de Factiliza API hardcodeado en el código fuente. | 🔴 **ALTA** | Exposición de credenciales de consumo de API externa en repositorio. |
| **INV-01** | `BillingController.php` | Las Notas de Crédito de anulación no restituyen el stock al almacén. | 🔴 **ALTA** | Descuadre permanente entre el inventario físico real y el stock contable en sistema. |
| **INV-02** | `SaleNoteController.php` | Anulación de Nota de Venta fuera de transacción, sin lock y con casteo entero destructivo. | 🔴 **ALTA** | Corrupción de stock decimal en anulaciones y riesgo de condición de carrera. |
| **CON-01** | `PosController.php` | Obtención del correlativo de la serie fuera de la transacción y sin `lockForUpdate`. | 🔴 **ALTA** | Colisiones de numeración de comprobantes ante ventas simultáneas en la misma caja. |
| **REP-01** | `ReportSalesController` | Reportes comerciales consultan únicamente `sale_notes`, ignorando `billings`. | 🔴 **ALTA** | Información financiera distorsionada para la toma de decisiones gerenciales. |
| **CHK-01** | `EventChecklistController` | Los checklists de retorno no afectan existencias físicas ni generan cobro por roturas. | 🟡 **MEDIA** | Menaje roto o perdido no genera descuento de kardex ni penalidades cobrables en caja. |
| **QTE-01** | `QuoteController.php` | Conversión de cotizaciones a venta no bloquea la cotización (permite doble venta). | 🟡 **MEDIA** | Riesgo de duplicar deducciones de stock de una misma propuesta comercial. |
| **CON-02** | `ContractController.php` | No existe estado de `Borrador/Preliminar` para pedidos públicos de clientes (B2). | 🟡 **MEDIA** | Impide el flujo de cotizaciones públicas o pedidos web sin firma inmediata. |
| **PER-01** | `routes/web.php` | Checklists y Menú de Cócteles reutilizan el permiso de Contratos (`can:admin.contracts`). | 🟡 **MEDIA** | Falta de granularidad en la asignación de permisos a personal de campo y bartenders. |
| **PRF-01** | `WarehouseController.php` | Métodos `list_products` y `get_prod_warehouse` cargan colecciones completas en RAM con `->get()`. | 🟢 **BAJA** | Degradación de memoria conforme crezca el catálogo de productos. |

---

## 9. Decisiones Requeridas del Usuario (Decisions Required From You)

Antes de iniciar la ejecución del roadmap en las fases B1 a B6, se requiere su definición sobre los siguientes 7 puntos arquitectónicos y de negocio:

1. **Impacto Físico de Checklists de Menaje (Bloque B4)**:
   - ¿El despacho del checklist (`items_llevados`) debe descontar stock del almacén central o trasladarlo a un almacén virtual tipo *"En Tránsito / Evento"*?
   - Cuando se reporte un ítem como `quebrado` o `faltante` en el retorno, ¿desea que el sistema descuente automáticamente la existencia del inventario activo mediante un movimiento de baja en Kardex?
2. **Financiamiento de Penalidades por Roturas (Bloque B4 / B6)**:
   - Al registrarse penalidades por menaje dañado o faltante en el checklist post-evento, ¿debe generarse automáticamente una cuota financiera adicional en el contrato, o debe crearse un comprobante de cobro directo (ticket de caja / Nota de Débito)?
3. **Flujo de Pre-Contrato para Pedidos Públicos (Bloques B1 / B2 / B3)**:
   - Dado que `contracts.estado` actualmente utiliza `0: Borrador`, `1: Firmado`, `2: Completado`, `3: Anulado`, ¿aprueba la incorporación formal de `estado = 4` (o `0` redefinido) como `PRELIMINARY / PRE-CONTRATO` para recepcionar pedidos públicos con nombre y teléfono desde la web antes de la formalización?
4. **Restitución Automática de Inventario en Notas de Crédito (Bloque B0 / Corrección Inmediata)**:
   - ¿Confirma que toda Nota de Crédito tipo `01` (Anulación total de la operación) debe restituir obligatoriamente el 100% de los productos físicos al almacén de origen del comprobante?
5. **Autenticación de la API REST SUNAT (Bloque B7)**:
   - ¿Los endpoints en `routes/api.php` (`/api/sunat/*`) deben protegerse mediante tokens personales con `Laravel Sanctum` o restringirse únicamente a la red local / sesión de usuario web?
6. **Consolidación de Reportes Comerciales (Bloque B6)**:
   - ¿Aprueba la unificación de `ReportSalesController` y `ReportPaymentController` para que consuman una vista o subquery unificada (`billings` UNION `sale_notes`) tal como opera actualmente el Cierre Diario de Caja?
7. **Migración de Credenciales Hardcodeadas a Variables de Entorno (Bloque B7)**:
   - ¿Autoriza trasladar el token de Factiliza API a `.env` (`FACTILIZA_API_TOKEN`) y parametrizar los textos legales de fallback del PDF de contratos para que lean exclusivamente la configuración de `Business`?

---

<p align="center">
  <b>MYTEMS E.I.R.L. &bull; Shipibo ERP</b><br>
  Auditoría de Línea Base B0 finalizada satisfactoriamente sin alteraciones al código productivo.
</p>
