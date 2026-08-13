<?php

namespace App\Http\Controllers;

use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        $event = Event::query()
            ->where('status', 'published')
            ->with(['categories.pricingTiers', 'paymentAccounts' => fn ($query) => $query->where('is_active', true)])
            ->first();

        return view('events.index', compact('event'));
    }

    public function show(Event $event)
    {
        abort_unless($event->status === 'published' || auth()->user()?->isAdmin(), 404);
        $event->load(['categories.pricingTiers', 'paymentAccounts' => fn ($q) => $q->where('is_active', true)]);

        return view('events.show', compact('event'));
    }
}
