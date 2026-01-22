@extends('Gateways::payment.layouts.master')

@section('content')
    <div style="max-width: 520px; margin: 40px auto; font-family: Arial, sans-serif;">
        <h2 style="margin-bottom: 8px;">Processing payment</h2>
        <div style="opacity: .8; margin-bottom: 16px;">Please complete the payment on your phone. This page will update automatically.</div>
        <div id="status" style="padding: 12px 14px; border: 1px solid #eee; border-radius: 10px; background: #fafafa;">Waiting for confirmation...</div>
    </div>

    <script>
        (function () {
            const paymentId = @json($payment_id);

            async function poll() {
                try {
                    const res = await fetch(@json(route('zenopay.status')) + '?payment_id=' + encodeURIComponent(paymentId), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();

                    if (data && data.message) {
                        document.getElementById('status').innerText = data.message;
                    }

                    if (data && data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                } catch (e) {
                    document.getElementById('status').innerText = 'Still processing...';
                }

                setTimeout(poll, 2500);
            }

            poll();
        })();
    </script>
@endsection
