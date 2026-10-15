<?php

return [

    /*
    |--------------------------------------------------------------------
    | Caller authentication
    |--------------------------------------------------------------------
    |
    | The secret a caller (a product's own backend, e.g. AxisField's /iq
    | proxy) must send as `Authorization: Bearer <this value>` to use
    | Core at all. Without it, a shared-token product would be queryable
    | by anyone who finds Core's URL, since Core holds that product's
    | downstream secret itself and would use it regardless of who asked.
    | See app/Http/Middleware/VerifyIqCallerToken.php.
    |
    */
    'caller_token' => env('IQ_CALLER_TOKEN'),

    /*
    |--------------------------------------------------------------------
    | Anthropic / Claude
    |--------------------------------------------------------------------
    |
    | Deliberately namespaced as IQ_ANTHROPIC_* rather than plain
    | ANTHROPIC_*: some hosts/containers set bare ANTHROPIC_* env vars for
    | their own unrelated tooling, and PHP's env() reads real process env
    | vars ahead of .env — a bare name here can silently pick up a value
    | that was never meant for this app (this bit us once in dev: an
    | ambient ANTHROPIC_BASE_URL override pointed requests at the wrong
    | host). Keep the IQ_ prefix even though it's more typing.
    |
    */
    'anthropic' => [
        'api_key' => env('IQ_ANTHROPIC_API_KEY'),
        'model' => env('IQ_ANTHROPIC_MODEL', 'claude-sonnet-5-5'),
        'max_tokens' => (int) env('IQ_ANTHROPIC_MAX_TOKENS', 1024),
        'base_url' => env('IQ_ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        'version' => env('IQ_ANTHROPIC_VERSION', '2023-06-01'),
        // Hard cap on tool-calling round trips per query, so a confused
        // model can't loop forever against a product's connector.
        'max_tool_iterations' => (int) env('IQ_ANTHROPIC_MAX_TOOL_ITERATIONS', 4),
    ],

    /*
    |--------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------
    |
    | One entry per AxisOne product. `base_url` is environment-driven so
    | the same code runs against the current unicornaxis.com dev URLs and,
    | later, the *.axisone.si production subdomains purely via .env.
    |
    | `tools` mirrors the contract documented in /connectors/<product>.md
    | and AGENT.md §6 — this array is what gets handed to Claude as the
    | available tool-calling schema for that product.
    |
    */
    'products' => [

        'field' => [
            'label' => 'Field',
            'base_url' => env('IQ_FIELD_URL', 'https://field.unicornaxis.com/app'),
            // AxisField (nosaas) is single-company with no per-user tenant scoping, so its
            // connector uses one shared service token instead of the end user's own session
            // (see /connectors/field.md "Auth"). Axis IQ Core holds this token server-side —
            // the widget never sees it, and it is NOT the token forwarded from the request.
            'auth' => [
                'type' => 'shared_token',
                'token' => env('IQ_FIELD_API_TOKEN'),
            ],
            'tools' => [
                [
                    'name' => 'field_technicians_working_today',
                    'description' => 'Count and status breakdown of technicians working today. count = on_site + en_route only (excludes off_today and available).',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/technicians-working-today'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'field_technicians',
                    'description' => "List of technicians matching a status/date filter. For a past date, buckets come from that day's recorded timesheets only.",
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/technicians'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['on_site', 'en_route', 'off_today', 'available']],
                            'date' => ['type' => 'string', 'description' => "YYYY-MM-DD or 'today'"],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'field_jobs_today',
                    'description' => "Jobs scheduled for today. 'count' reflects the status filter, but 'completed' and 'open' in the response are always totals regardless of the filter.",
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/jobs-today'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['open', 'completed', 'all']],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'field_job_completion_rate',
                    'description' => 'Percentage of scheduled jobs completed in the given period. Weeks start Monday.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/job-completion-rate'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'period' => ['type' => 'string', 'enum' => ['today', 'this_week', 'this_month']],
                        ],
                        'required' => [],
                    ],
                ],
            ],
        ],

        'view' => [
            'label' => 'View',
            'base_url' => env('IQ_VIEW_URL', 'https://unicornaxis.com/view/app'),
            // Placeholder default — not yet confirmed against View's actual auth model
            // (see connectors/field.md for why this can't just be assumed: Field turned
            // out to use a shared token, not per-user forwarding, once actually built).
            'auth' => ['type' => 'forward_user_token'],
            'tools' => [
                [
                    'name' => 'view_rfi_count',
                    'description' => 'Count of RFIs matching a status, for the current tenant/project.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/rfi-count'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['open', 'closed', 'overdue', 'all']],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'view_rfis',
                    'description' => 'List of RFIs matching a filter.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/rfis'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['open', 'closed', 'overdue', 'all']],
                            'project_id' => ['type' => 'string'],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'view_submittal_count',
                    'description' => 'Count of submittals by status.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/submittal-count'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected', 'all']],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'view_overdue_items',
                    'description' => 'Count and breakdown of all overdue items (RFIs, submittals, punch list) across the project.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/overdue-items'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                        'required' => [],
                    ],
                ],
            ],
        ],

        'financial' => [
            'label' => 'Financial',
            'base_url' => env('IQ_FINANCIAL_URL', 'https://unicornaxis.com/financial/app'),
            // Placeholder default — confirm against Financial's actual auth model when built.
            'auth' => ['type' => 'forward_user_token'],
            'tools' => [
                [
                    'name' => 'financial_invoice_summary',
                    'description' => 'Count and total amount of invoices matching a status/period.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/invoice-summary'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['paid', 'unpaid', 'overdue', 'all']],
                            'period' => ['type' => 'string', 'enum' => ['this_month', 'last_month', 'this_year']],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'financial_budget_vs_actual',
                    'description' => 'Budgeted vs actual spend for a project/period.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/budget-vs-actual'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'project_id' => ['type' => 'string'],
                            'period' => ['type' => 'string', 'enum' => ['this_month', 'this_quarter', 'this_year']],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'financial_cash_position',
                    'description' => "Current cash balance across the tenant's connected accounts.",
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/cash-position'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'financial_overdue_receivables',
                    'description' => 'Count and total of invoices overdue for payment from clients.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/overdue-receivables'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                        'required' => [],
                    ],
                ],
            ],
        ],

        'flow' => [
            'label' => 'Flow',
            'base_url' => env('IQ_FLOW_URL', 'https://unicornaxis.com/flow/app'),
            // Placeholder default — confirm against Flow's actual auth model when built.
            'auth' => ['type' => 'forward_user_token'],
            'tools' => [
                [
                    'name' => 'flow_task_count',
                    'description' => 'Count of workflow tasks matching a status.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/task-count'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['pending', 'in_progress', 'completed', 'overdue', 'all']],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'flow_tasks',
                    'description' => 'List of tasks matching a filter.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/tasks'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['pending', 'in_progress', 'completed', 'overdue', 'all']],
                            'assigned_to' => ['type' => 'string'],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'flow_approval_queue',
                    'description' => "Count and list of items currently awaiting the requesting user's approval.",
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/approval-queue'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'flow_average_cycle_time',
                    'description' => 'Average time tasks/approvals take to complete in a workflow.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/average-cycle-time'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'workflow' => ['type' => 'string'],
                            'period' => ['type' => 'string', 'enum' => ['this_month', 'this_quarter']],
                        ],
                        'required' => [],
                    ],
                ],
            ],
        ],

        'estimate' => [
            'label' => 'Estimate',
            'base_url' => env('IQ_ESTIMATE_URL', 'https://unicornaxis.com/estimate'),
            // Placeholder default — confirm against Estimate's actual auth model when built.
            'auth' => ['type' => 'forward_user_token'],
            'tools' => [
                [
                    'name' => 'estimate_open_estimates_count',
                    'description' => 'Count of estimates matching a status.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/open-estimates-count'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['draft', 'submitted', 'won', 'lost', 'all']],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'estimate_estimates',
                    'description' => 'List of estimates matching a filter.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/estimates'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['draft', 'submitted', 'won', 'lost', 'all']],
                            'client_id' => ['type' => 'string'],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'estimate_win_rate',
                    'description' => 'Percentage of submitted estimates that converted to won jobs in a period.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/win-rate'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'period' => ['type' => 'string', 'enum' => ['this_quarter', 'this_year']],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name' => 'estimate_pending_approval',
                    'description' => 'Count and list of estimates awaiting internal approval before being sent to the client.',
                    'endpoint' => ['method' => 'GET', 'path' => '/api/iq/pending-approval'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                        'required' => [],
                    ],
                ],
            ],
        ],

    ],

];
