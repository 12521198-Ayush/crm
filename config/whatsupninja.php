<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP client
    |--------------------------------------------------------------------------
    */
    'request_timeout' => env('WHATSUPNINJA_TIMEOUT', 20),

    // Default API path appended to the connection base_url (the WhatsupNinja
    // instance may live behind a locale prefix; include it in base_url if so).
    'api_path' => '/api',

    // Treat a cached JWT as expired this many minutes before its nominal expiry.
    'jwt_skew_minutes' => 2,

    // Scheduled template sync cadence (hours).
    'sync_interval_hours' => env('WHATSUPNINJA_SYNC_HOURS', 6),

    /*
    |--------------------------------------------------------------------------
    | Trigger event catalog (single source for backend + builder UI)
    |--------------------------------------------------------------------------
    | `key` is stored on automation_rules.trigger_event. `context` lists the
    | extra data available to conditions/variables for that event.
    */
    'events' => [
        ['key' => 'lead.created',         'label' => 'New Lead Created',  'group' => 'Lead'],
        ['key' => 'lead.imported',        'label' => 'Lead Imported',     'group' => 'Lead'],
        ['key' => 'lead.assigned',        'label' => 'Lead Assigned',     'group' => 'Lead'],
        ['key' => 'lead.reassigned',      'label' => 'Lead Reassigned',   'group' => 'Lead'],
        ['key' => 'lead.status_changed',  'label' => 'Lead Status Changed','group' => 'Status'],
        ['key' => 'followup.due',         'label' => 'Follow-up Due',     'group' => 'Follow-up'],
        ['key' => 'followup.overdue',     'label' => 'Follow-up Overdue', 'group' => 'Follow-up'],
        ['key' => 'meeting.due',          'label' => 'Meeting Reminder',  'group' => 'Meeting'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Condition operators
    |--------------------------------------------------------------------------
    */
    'operators' => [
        ['key' => 'equals',      'label' => 'Equals',           'value' => 'single'],
        ['key' => 'not_equals',  'label' => 'Does not equal',   'value' => 'single'],
        ['key' => 'in',          'label' => 'Is any of',        'value' => 'multi'],
        ['key' => 'not_in',      'label' => 'Is none of',       'value' => 'multi'],
        ['key' => 'changed_to',  'label' => 'Changed to',       'value' => 'single'],
        ['key' => 'is_set',      'label' => 'Is set',           'value' => 'none'],
        ['key' => 'is_empty',    'label' => 'Is empty',         'value' => 'none'],
        ['key' => 'contains',    'label' => 'Contains',         'value' => 'single'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Condition fields (lead attributes + relationships)
    |--------------------------------------------------------------------------
    */
    'fields' => [
        ['key' => 'status_id',   'label' => 'Status',    'type' => 'status'],
        ['key' => 'source_id',   'label' => 'Source',    'type' => 'source'],
        ['key' => 'project_id',  'label' => 'Project',   'type' => 'project'],
        ['key' => 'assigned_to', 'label' => 'Assignee',  'type' => 'user'],
        ['key' => 'city',        'label' => 'City',      'type' => 'text'],
        ['key' => 'sub_source',  'label' => 'Sub Source','type' => 'text'],
        ['key' => 'budget',      'label' => 'Budget',    'type' => 'number'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template variables — CRM tokens usable inside templates / variable maps.
    | `key` is the token name; `path` is resolved by VariableResolver.
    |--------------------------------------------------------------------------
    */
    'variables' => [
        ['key' => 'lead_name',    'label' => 'Lead Name'],
        ['key' => 'mobile',       'label' => 'Mobile'],
        ['key' => 'email',        'label' => 'Email'],
        ['key' => 'source',       'label' => 'Source'],
        ['key' => 'status',       'label' => 'Status'],
        ['key' => 'agent_name',   'label' => 'Agent Name'],
        ['key' => 'project_name', 'label' => 'Project Name'],
        ['key' => 'meeting_date', 'label' => 'Meeting / Follow-up Date'],
        ['key' => 'company_name', 'label' => 'Company Name'],
        ['key' => 'city',         'label' => 'City'],
    ],

    // Static fallback for {{company_name}} when not lead-specific.
    'company_name' => env('APP_NAME', 'Our Company'),
];
