@once
    <style>
        .leave-review { width:100%; min-width:0; color:inherit; }
        .leave-review h2, .leave-review h3, .leave-review p { margin:0; }
        .leave-review h2 { font-size:20px; font-weight:650; }
        .leave-review h3 { font-size:16px; font-weight:650; }
        .leave-review .muted { color:#64748b; font-size:13px; }
        .dark .leave-review .muted { color:#a1a1aa; }
        .leave-review .overview { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; padding:0 0 24px; border-bottom:1px solid #e4e4e7; }
        .dark .leave-review .overview, .dark .leave-review .band { border-color:#3f3f46; }
        .leave-review .details-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:24px; margin:24px 0; }
        .leave-review dt { font-size:13px; color:#64748b; margin-bottom:6px; }
        .dark .leave-review dt { color:#a1a1aa; }
        .leave-review dd { margin:0; font-size:14px; overflow-wrap:anywhere; }
        .leave-review .columns { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(0,1fr); gap:32px; }
        .leave-review .band { border-top:1px solid #e4e4e7; padding:24px 0; }
        .leave-review .prose { white-space:pre-wrap; overflow-wrap:anywhere; margin-top:12px; font-size:14px; line-height:1.65; }
        .leave-review .timeline { list-style:none; padding:0; margin:20px 0 0; }
        .leave-review .timeline li { border-left:2px solid #d4d4d8; padding:0 0 22px 18px; margin-left:5px; overflow-wrap:anywhere; }
        .leave-review .timeline li.current { border-color:#2563eb; }
        .leave-review .step-line { display:flex; gap:12px; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:6px; }
        .leave-review .step-name { font-size:14px; font-weight:600; }
        .leave-review .audit { width:100%; border-collapse:collapse; font-size:13px; }
        .leave-review .audit td, .leave-review .audit th { text-align:left; padding:12px 16px 12px 0; border-bottom:1px solid #e4e4e7; vertical-align:top; }
        .dark .leave-review .audit td, .dark .leave-review .audit th { border-color:#3f3f46; }
        .leave-review .scroll { overflow-x:auto; margin-top:16px; }
        .leave-review .attachment { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-top:12px; }
        .leave-review .route { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-top:12px; }
        @media(max-width:900px) { .leave-review .columns { grid-template-columns:minmax(0,1fr); gap:0; } }
        @media(max-width:600px) { .leave-review .details-grid { grid-template-columns:minmax(0,1fr); gap:16px; } .leave-review .overview { flex-direction:column; } }
    </style>
@endonce
