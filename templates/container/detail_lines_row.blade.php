<tr class="detail-line-row" data-index="{{ $index ?? 'NEW_INDEX' }}">
    @if($container->isSortable())
        <td class="align-middle text-center cursor-move handle">
            <i class="fas fa-grip-lines text-muted"></i>
            {{-- Hidden input for order tracking --}}
            <input type="hidden" name="lines[{{ $index ?? 'NEW_INDEX' }}][order]" value="{{ $index ?? 0 }}" class="line-order-input">
        </td>
    @endif
    
    @foreach($container->getLineColumns() as $col)
        <td class="align-middle">
            @php
                // Resolve value from the line record
                $val = null;
                $fName = $col->getField();
                
                if (isset($lineRecord)) {
                    if (is_array($lineRecord)) {
                        $val = $lineRecord[$fName] ?? null;
                    } elseif (is_object($lineRecord)) {
                        try {
                            $val = $lineRecord->$fName ?? null;
                        } catch(\Exception $e) {
                            $val = null;
                        }
                    }
                }
                
                // Adjust field name to be an array input (e.g. lines[0][cantidad])
                // We clone the component to change its name dynamically without affecting the definition
                $colClone = clone $col;
                // In Alxarafe, fields use the generic render method. We pass the explicit 'name'
                $inputName = "lines[" . ($index ?? 'NEW_INDEX') . "][" . $fName . "]";
            @endphp
            
            {{-- We render the field component but passing the mapped name and value --}}
            {!! $colClone->render(['name' => $inputName, 'value' => $val]) !!}
        </td>
    @endforeach
    
    @if($container->canRemoveRow())
        <td class="align-middle text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line" title="{{ \Alxarafe\Infrastructure\Lib\Trans::_('remove_line') }}">
                <i class="fas fa-trash"></i>
            </button>
            {{-- Hidden input to mark for deletion --}}
            <input type="hidden" name="lines[{{ $index ?? 'NEW_INDEX' }}][_delete]" value="0" class="line-delete-input">
        </td>
    @endif
</tr>
