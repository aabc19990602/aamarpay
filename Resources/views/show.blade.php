<div>
    <div class="d-none">
        @if (!empty($setting['name']))
            <h2>{{ $setting['name'] }}</h2>
        @endif

        @if ($setting['mode'] == 'sandbox')
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> {{ trans('paypal-standard::general.test_mode') }}</div>
        @endif

        <div class="well well-sm">
            {{ trans('paypal-standard::general.description') }}
        </div>
    </div>
    <br>

    <div class="buttons">
        <div class="pull-right">
            

            <form name="redirectpost" method="post" action="<?php echo 'https://sandbox.aamarpay.com/'.$url; ?>">
                <input type="submit" value="{{ trans('general.confirm') }}" class="btn btn-success" />
            </form>



        </div>
    </div>
</div>
