<div class="{{ $class ?? 'col-12' }} mb-3" id="container-{{ $field }}">
    <div class="card shadow-sm border-secondary detail-lines-card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i> {{ \Alxarafe\Infrastructure\Lib\Trans::_($label) }}
            </h5>
            @if($container->canAddRow())
            <button type="button" class="btn btn-sm btn-primary btn-add-line" data-target="{{ $field }}">
                <i class="fas fa-plus"></i> {{ \Alxarafe\Infrastructure\Lib\Trans::_('add_line') }}
            </button>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 detail-lines-table" id="table-{{ $field }}" data-model="{{ $container->getModelClass() }}" data-fk="{{ $container->getForeignKey() }}">
                    <thead>
                        <tr>
                            @if($container->isSortable())
                                <th style="width: 40px; text-align: center;"><i class="fas fa-sort text-muted"></i></th>
                            @endif
                            
                            @foreach($container->getLineColumns() as $col)
                                @php
                                    $colOpts = $col->getOptions()['options'] ?? [];
                                @endphp
                                <th class="{{ $colOpts['col'] ?? '' }}">
                                    {{ \Alxarafe\Infrastructure\Lib\Trans::_($col->getLabel()) }}
                                </th>
                            @endforeach
                            
                            @if($container->canRemoveRow())
                                <th style="width: 50px;"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="detail-lines-body" id="tbody-{{ $field }}">
                        {{-- 
                            Renderizado de filas existentes.
                            El $record en este contexto es el objeto principal (Ej: FacturaCliente).
                            Podemos iterar sobre su relación.
                        --}}
                        @php
                            $lines = [];
                            $relationMethod = $field; // Generalmente el nombre del campo es el nombre de la relación
                            if (isset($record) && is_object($record) && method_exists($record, $relationMethod)) {
                                $lines = $record->$relationMethod;
                            } elseif (isset($record) && is_object($record) && method_exists($record, 'getLines')) {
                                // Fallback para modelos legacy de Tahiche
                                $lines = $record->getLines();
                            }
                        @endphp
                        
                        @forelse($lines as $index => $lineRecord)
                            @include('container.detail_lines_row', ['container' => $container, 'lineRecord' => $lineRecord, 'index' => $index])
                        @empty
                            <tr class="empty-state-row">
                                <td colspan="100%" class="text-center text-muted p-4">
                                    {{ \Alxarafe\Infrastructure\Lib\Trans::_('no_lines_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    
                    {{-- Template for new rows (hidden) --}}
                    <template id="template-{{ $field }}">
                        @include('container.detail_lines_row', [
                            'container' => $container, 
                            'lineRecord' => null, 
                            'index' => '__INDEX__'
                        ])
                    </template>
                    
                    @if(!empty($container->getFooterTotals()))
                    <tfoot class="bg-light">
                        <tr>
                            @if($container->isSortable()) <th></th> @endif
                            
                            @php
                                $columns = $container->getLineColumns();
                                $totals = $container->getFooterTotals();
                                $colspan = count($columns) - count($totals);
                            @endphp
                            
                            @if($colspan > 0)
                            <td colspan="{{ $colspan }}" class="text-end fw-bold align-middle">
                                {{ \Alxarafe\Infrastructure\Lib\Trans::_('totals') }}:
                            </td>
                            @endif
                            
                            {{-- Alineamos los totales con sus respectivas columnas --}}
                            @foreach($columns as $idx => $col)
                                @if($idx >= $colspan)
                                    @php
                                        $colName = $col->getField();
                                    @endphp
                                    <td class="fw-bold fs-5 text-end">
                                        @if(isset($totals[$colName]))
                                            <span id="total-{{ $colName }}-{{ $field }}">0.00</span>
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                            
                            @if($container->canRemoveRow()) <th></th> @endif
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
<script src="/js/detail-lines.js" defer></script>
