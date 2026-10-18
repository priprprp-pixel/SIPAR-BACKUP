<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Masuk') · SIPAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root{--ink:#102334;--muted:#72808d;--line:#e1e8ed;--blue:#0868ce}*{box-sizing:border-box}body{min-height:100vh;margin:0;background:#eef3f6;font:14px 'DM Sans',sans-serif;color:var(--ink);display:grid;place-items:center;padding:0}.auth-frame{width:100%;min-height:100vh;background:#fff;display:grid;grid-template-columns:1fr 1fr;border:0;border-radius:0;overflow:hidden;box-shadow:none}.auth-aside{background:#102334;color:#fff;padding:clamp(28px,6vw,84px);display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden}.auth-aside:after{content:'';position:absolute;width:310px;height:310px;right:-125px;bottom:-145px;border:1px solid #ffffff20;border-radius:50%;box-shadow:0 0 0 30px #ffffff08,0 0 0 60px #ffffff06}.brand{font:800 15px Manrope;color:#fff;text-decoration:none;display:flex;align-items:center;gap:10px}.brand small{display:block;font:400 10px 'DM Sans';color:#9eafbd}.brand-mark{width:35px;height:35px;background:#0874e3;border-radius:9px;display:grid;place-items:center}.aside-copy{position:relative;z-index:1;max-width:355px}.aside-copy small{font-size:10px;color:#9eb3c3}.aside-copy h1{font:800 28px/1.2 Manrope;margin:16px 0}.aside-copy p{font-size:12px;color:#b1c0ca;line-height:1.7}.aside-foot{font-size:10px;color:#8ca1b2;border-top:1px solid #ffffff20;padding-top:16px}.auth-main{padding:42px;display:grid;place-items:center}.auth-form{width:min(100%,340px)}.auth-form h2{font:800 22px Manrope;margin:0 0 5px}.auth-form>p{font-size:11px;color:var(--muted);margin-bottom:22px}.form-label{font-size:11px;font-weight:600}.form-control,.form-select{font-size:12px;border-color:var(--line);padding:10px;border-radius:6px}.btn-primary{font-size:12px;font-weight:700;background:var(--blue);border-color:var(--blue);padding:10px}.role-switch{display:grid;grid-template-columns:1fr 1fr;background:#f2f5f7;border-radius:6px;padding:3px;margin-bottom:18px}.role-switch a{color:#687987;text-decoration:none;text-align:center;padding:7px;font-size:11px;border-radius:5px}.role-switch a.active{background:#fff;color:var(--ink);box-shadow:0 1px 3px #15283c15}.help-note{background:#f5f8fa;border:1px solid var(--line);border-radius:6px;padding:10px;font-size:10px;color:var(--muted)}.alert{font-size:12px}
        @media(max-width:700px){body{padding:0;background:#fff;place-items:stretch}.auth-frame{border:0;border-radius:0;min-height:100vh;grid-template-columns:1fr;box-shadow:none}.auth-aside{min-height:190px;padding:22px 24px}.auth-aside .aside-copy{margin-top:28px}.aside-copy h1{font-size:20px;margin:8px 0}.aside-copy p,.aside-foot{display:none}.auth-main{padding:30px 24px;align-items:start}.auth-form{width:100%;max-width:410px}}
    </style>
    <style>
        :root{--auth-ink:#132c3a;--auth-muted:#667987;--auth-line:#dce5eb;--auth-blue:#0877d8;--auth-blue-dark:#075da8;--auth-surface:#f3f6f7}
        body{background:#eaf0f2;color:var(--auth-ink);padding:24px}
        .auth-frame{width:min(100%,1280px);min-height:min(820px,calc(100svh - 48px));grid-template-columns:minmax(0,1.05fr) minmax(480px,.95fr);max-width:1280px;margin:auto;border:1px solid #dce5e9;border-radius:14px;box-shadow:0 24px 64px rgba(19,44,58,.12)}
        .auth-aside{isolation:isolate;background-color:#122b3a;background-image:url('https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1800&q=85');background-position:center;background-size:cover;padding:clamp(32px,5vw,72px)}
        .auth-aside::before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(120deg,rgba(9,44,57,.48) 0%,rgba(11,70,81,.32) 52%,rgba(7,36,56,.45) 100%)}
        .auth-aside::after{content:none}
        .auth-aside>.brand,.auth-aside>.aside-copy,.auth-aside>.aside-foot{position:relative;z-index:1}
        .brand{gap:12px;padding-bottom:0;font-size:16px}
        .auth-aside .brand small{font:500 10px/1.35 'DM Sans',sans-serif;color:#fff;text-shadow:0 1px 8px rgba(4,20,29,.6)}
        .brand-mark{width:40px;height:40px;border-radius:10px;background:#0877d8}
        .aside-copy{max-width:480px}
        .aside-copy small{display:block;max-width:100%;font-size:11px;font-weight:700;line-height:1.55;color:#fff;text-shadow:0 1px 8px rgba(4,20,29,.65)}
        .aside-copy h1{max-width:440px;font:700 36px/1.16 Manrope,sans-serif;margin:17px 0 12px;text-shadow:0 2px 14px rgba(4,20,29,.44)}
        .aside-copy p{max-width:410px;font-size:15px;font-weight:600;line-height:1.65;color:#fff;text-shadow:0 1px 9px rgba(4,20,29,.58)}
        .aside-foot{font-size:11px;font-weight:600;color:#fff;border-color:#ffffff65;text-shadow:0 1px 8px rgba(4,20,29,.6)}
        .auth-main{padding:clamp(28px,5vw,64px);background:#f1f5f6}
        .auth-form{width:min(100%,430px);padding:38px;background:#fff;border:1px solid #e3e9ed;border-radius:12px;box-shadow:0 18px 50px rgba(18,43,58,.08);animation:auth-enter .35s ease-out both}
        .auth-form h2{font-size:25px;color:var(--auth-ink)}
        .auth-form>p{font-size:13px;line-height:1.55;color:var(--auth-muted);margin:7px 0 24px}
        .role-switch{gap:3px;padding:4px;background:#edf2f5;border:1px solid #e2e9ed;border-radius:8px;margin-bottom:22px}
        .role-switch button{min-height:40px;padding:8px 10px;border:0;border-radius:6px;background:transparent;color:#536977;font:600 12px 'DM Sans',sans-serif;white-space:nowrap;transition:background-color .16s ease,color .16s ease,box-shadow .16s ease}
        .role-switch button:hover{color:var(--auth-ink)}
        .role-switch button.active{background:#fff;color:var(--auth-blue-dark);box-shadow:0 1px 4px rgba(19,44,58,.14)}
        .role-switch button:focus-visible,.auth-form button:focus-visible,.auth-form a:focus-visible{outline:3px solid #9ed1f7;outline-offset:2px}
        .auth-form .form-label{font-size:12px;color:#304957;margin-bottom:7px}
        .auth-form .form-control{min-height:46px;padding:11px 13px;border:1px solid var(--auth-line);border-radius:7px;background:#fff;font-size:14px;color:var(--auth-ink)}
        .auth-form .form-control::placeholder{color:#8a9aa5}
        .auth-form .form-control:focus{border-color:#61a9e4;box-shadow:0 0 0 3px rgba(8,119,216,.13)}
        .password-field{position:relative}
        .password-field .form-control{width:100%}
        .password-toggle{position:absolute;top:50%;right:7px;display:grid;width:34px;height:34px;place-items:center;transform:translateY(-50%);border:0;border-radius:6px;background:transparent;color:var(--auth-muted)}
        .password-toggle:hover{background:var(--auth-surface);color:var(--auth-blue-dark)}
        .password-toggle .bi{font-size:16px}
        .auth-form .form-check-input{width:16px;height:16px;margin-top:.1em}
        .auth-form .form-check-input:checked{background-color:var(--auth-blue);border-color:var(--auth-blue)}
        .auth-form .form-check-label,.auth-form .text-muted{font-size:12px;color:var(--auth-muted)!important}
        .login-options{gap:12px}
        .login-options #login-support{max-width:62%;font-weight:600;line-height:1.45;text-align:right}
        .auth-form .btn-primary{min-height:46px;border:0;border-radius:7px;background:var(--auth-blue);font-size:13px;box-shadow:0 4px 10px rgba(8,119,216,.15)}
        .auth-form .btn-primary:hover{background:var(--auth-blue-dark)}
        .help-note{padding:12px 13px;border:1px solid #e4ebef;border-radius:7px;background:#f7f9fa;color:#687b87;font-size:11px;line-height:1.6}
        .help-note a{color:var(--auth-blue-dark);font-weight:700;text-decoration:none}
        .help-note a:hover{text-decoration:underline}
        .auth-form .alert{border-radius:7px;font-size:12px}
        @keyframes auth-enter{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:991.98px){
            .auth-frame{grid-template-columns:minmax(0,1fr) minmax(430px,.95fr)}
            .auth-aside{padding:34px}
            .aside-copy h1{font-size:30px}
            .auth-main{padding:28px}
            .auth-form{padding:30px}
        }
        @media(max-width:767.98px){
            body{display:block;min-height:100svh;padding:12px;background:#eaf0f2}
            .auth-frame{width:100%;min-height:auto;grid-template-columns:minmax(0,1fr);grid-template-rows:auto auto;margin:0 auto;border-radius:12px;box-shadow:0 12px 32px rgba(19,44,58,.1)}
            .auth-aside{min-height:240px;padding:20px 22px 24px;border-radius:11px 11px 0 0}
            .auth-aside .aside-copy{margin-top:29px}
            .aside-copy small{font-size:10px}
            .aside-copy h1{max-width:520px;font-size:23px;margin:8px 0}
            .aside-copy p{display:block;max-width:520px;font-size:13px;font-weight:600;line-height:1.5;margin:0}
            .aside-foot{display:none}
            .auth-main{position:relative;z-index:2;display:block;margin-top:-18px;padding:0 16px 20px;background:transparent}
            .auth-form{width:100%;max-width:440px;margin:0 auto;padding:24px;background:#fff;border:1px solid #e0e7eb;border-radius:10px;box-shadow:0 10px 26px rgba(19,44,58,.1);animation:none}
            .auth-form h2{font-size:22px}
            .auth-form>p{font-size:12px;margin:6px 0 20px}
            .role-switch button{font-size:12px}
            .login-options{align-items:flex-start!important;gap:8px;margin-bottom:20px!important}
            .login-options #login-support{max-width:68%;font-size:11px;text-align:left}
        }
        @media(max-width:380px){
            .auth-aside{min-height:226px;padding-right:18px;padding-left:18px}
            .auth-main{padding-right:12px;padding-left:12px}
            .auth-form{padding:20px}
            .role-switch button{padding-right:6px;padding-left:6px;font-size:11px}
            .auth-form .text-muted{font-size:11px}
        }
        @media(max-width:767.98px) and (max-height:640px){
            .auth-aside{min-height:205px;padding-top:16px;padding-bottom:18px}
            .auth-aside .aside-copy{margin-top:20px}
            .aside-copy h1{font-size:21px}
            .auth-main{padding-top:18px}
            .auth-form>p{margin-bottom:15px}
            .role-switch{margin-bottom:16px}
        }
        @media(prefers-reduced-motion:reduce){.auth-form{animation:none}.role-switch button{transition:none}}
    </style>
</head>
<body>@yield('content')</body>
</html>
