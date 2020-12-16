@extends( config('laravel-h5p.layout') )

@section( 'h5p' )
<div class="container-fluid">

    <div class="row" style="margin-bottom: 10px;">
        <div class="col-md-3">
            <a href="{{ route("h5p.create") }}" class="btn btn-primary pull-right">{{ trans('laravel-h5p.content.create') }}</a>
        </div>
    </div>

    <div class="row">

        <div class="col-md-12">

            <table class="table text-middle text-center h5p-lists table-hover table-striped">
                <colgroup>
                    <col width="10%">
                    <col width="15%">
                    <col width="*">
                    <col width="10%">
                    <col width="15%">
                </colgroup>

                <thead>
                    <tr class="active">
                        <th class="text-center">#</th>
                        <th class="text-center">{{ trans('laravel-h5p.content.creator') }}</th>
                        <th class="text-left">{{ trans('laravel-h5p.content.title') }}</th>
                        <th class="text-center">{{ trans('laravel-h5p.content.created_at') }}</th>
                        <th class="text-center">{{ trans('laravel-h5p.content.action') }}</th>
                    </tr>
                </thead>

                <tbody>

                    @unless(count($entrys) >0)
                    <tr><td colspan="5" class="h5p-noresult">{{ trans('laravel-h5p.common.no-result') }}</td></tr>
                    @endunless

                    @foreach($entrys as $n => $entry)
                    <tr>

                        <td class="">
                            {{ $entry->id }}
                        </td>

                        <td class="">
                            {{ $entry->get_user()->name }}
                        </td>

                        <td class="text-left">
                            <a href="{{ route('h5p.show', $entry->id) }}">{{ $entry->title }}</a>
                        </td>

                        <td class="">
                            {{ $entry->updated_at->format('Y.m.d') }}
                        </td>

                        <td>
                            <a href="{{ route('h5p.edit', $entry->id) }}" class="btn btn-default"  data-tooltip="{pos:'top'}" title="{{ trans('laravel-h5p.content.edit') }}">{{ trans('laravel-h5p.content.edit') }}</a>

                            <button class="btn btn-danger h5p-delete" data-delete="{{ route('h5p.destroy', $entry->id) }}" data-tooltip="{pos:'top'}" title="{{ trans('laravel-h5p.content.destroy') }}">{{ trans('laravel-h5p.content.destroy') }}</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

    </div>


    <div class="row">

        <div class="col-md-12 text-center" style='margin-top:20px;'>
            {!! $entrys->render() !!}
        </div>

    </div>

</div>

@endsection


@push( 'h5p-header-script' )
@endpush

@push( 'h5p-footer-script' )
<script type="text/javascript">

    H5P.jQuery(document).ready(function () {

        H5P.jQuery('.h5p-delete').on('click', function () {

            var $obj = H5P.jQuery(this);
            var msg = "{{ trans('laravel-h5p.content.confirm_destroy') }}";
            if (confirm(msg)) {

                H5P.jQuery.ajax({
                    url: $obj.data('delete'),
                    method: "DELETE",
                    headers: {
                        'X-CSRF-TOKEN': H5P.jQuery('meta[name="csrf-token"]').attr('content'),
                    },
                    success: function (data) {
                        location.reload();
                    },
                    error: function () {
                        alert("{{ trans('laravel-h5p.content.can_not_delete') }}");
                    }
                })
            }

        });
    });

</script>
@endpush
