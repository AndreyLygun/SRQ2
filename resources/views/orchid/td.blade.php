<td class="text-{{$align}} {{$css_class}} @if(!$width) text-truncate @endif"
    data-column="{{ $slug }}" colspan="{{ $colspan }}"
    @empty(!$width)style="min-width:{{ is_numeric($width) ? $width . 'px' : $width }};" @endempty>
    <div>
        @isset($render)
            {!! $value !!}
        @else
            {{ $value }}
        @endisset
    </div>
</td>
