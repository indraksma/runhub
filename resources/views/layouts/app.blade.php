<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RunHub')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon_abba.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon_abba.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon_abba.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --ink: #111413;
            --paper: #f4f2ed;
            --white: #fff;
            --lime: #c8ff3d;
            --red: #ee4b2b;
            --muted: #6e726f;
            --line: #d9d7d0;
            --green: #207a50;
            --shadow: 0 18px 50px rgba(22, 25, 23, .09)
        }

        * {
            box-sizing: border-box
        }

        [hidden] {
            display: none !important
        }

        html {
            scroll-behavior: smooth
        }

        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font-family: "DM Sans", sans-serif;
            line-height: 1.55
        }

        a {
            color: inherit;
            text-decoration: none
        }

        img {
            max-width: 100%
        }

        .wrap {
            width: min(1160px, calc(100% - 32px));
            margin: auto
        }

        .nav {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(244, 242, 237, .92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--line)
        }

        .nav-inner {
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font: 800 22px Manrope;
            line-height: 1
        }

        .brand img {
            display: block;
            flex: 0 1 auto;
            width: auto;
            max-width: 34px;
            height: 34px;
            object-fit: contain
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            font-weight: 600;
            font-size: 14px
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            border: 0;
            border-radius: 999px;
            padding: 12px 20px;
            background: var(--ink);
            color: white;
            font: 700 14px "DM Sans";
            cursor: pointer
        }

        .btn:hover {
            transform: translateY(-1px)
        }

        .btn-lime {
            background: var(--lime);
            color: var(--ink)
        }

        .btn-light {
            background: var(--white);
            color: var(--ink);
            border: 1px solid var(--line)
        }

        .btn-red {
            background: var(--red)
        }

        .btn-sm {
            padding: 8px 14px;
            font-size: 13px
        }

        main {
            min-height: calc(100vh - 160px)
        }

        .hero {
            padding: 88px 0 66px;
            overflow: hidden
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: .16em;
            font-size: 12px;
            font-weight: 800;
            color: var(--red)
        }

        h1,
        h2,
        h3 {
            font-family: Manrope;
            margin: 0;
            line-height: 1.08
        }

        h1 {
            font-size: clamp(48px, 8vw, 96px);
            letter-spacing: -.065em;
            max-width: 920px
        }

        .accent {
            position: relative;
            white-space: nowrap
        }

        .accent:after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 4px;
            height: 10px;
            background: var(--lime);
            z-index: -1;
            transform: rotate(-1deg)
        }

        .hero p {
            max-width: 600px;
            color: var(--muted);
            font-size: 18px;
            margin: 24px 0 30px
        }

        .section {
            padding: 64px 0
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 16px;
            margin-bottom: 28px
        }

        .section h2 {
            font-size: clamp(30px, 5vw, 48px);
            letter-spacing: -.04em
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px
        }

        .card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow)
        }

        .card-body {
            padding: 24px
        }

        .event-cover {
            height: 200px;
            background: linear-gradient(135deg, #1a201d, #38433c);
            position: relative;
            display: flex;
            align-items: end;
            padding: 22px;
            color: white;
            background-size: cover;
            background-position: center
        }

        .event-cover:after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, .7))
        }

        .event-cover>* {
            position: relative;
            z-index: 1
        }

        .date-chip {
            position: absolute !important;
            top: 18px;
            left: 18px;
            background: var(--lime);
            color: var(--ink);
            border-radius: 14px;
            padding: 8px 12px;
            text-align: center;
            font-weight: 800;
            line-height: 1.05
        }

        .date-chip span {
            display: block;
            font-size: 11px;
            text-transform: uppercase
        }

        .meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: 14px
        }

        .price {
            font: 800 22px Manrope
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px
        }

        .stack {
            display: grid;
            gap: 16px
        }

        .badge {
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e9ebe8;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em
        }

        .badge.verified,
        .badge.published {
            background: #d9f6e6;
            color: #17603d
        }

        .badge.rejected {
            background: #ffe0da;
            color: #a62d18
        }

        .badge.awaiting_verification {
            background: #fff0c8;
            color: #865f00
        }

        .form-shell {
            width: min(760px, calc(100% - 32px));
            margin: 52px auto
        }

        .panel {
            background: white;
            border: 1px solid var(--line);
            border-radius: 26px;
            padding: clamp(22px, 5vw, 38px);
            box-shadow: var(--shadow)
        }

        .panel h1 {
            font-size: clamp(32px, 6vw, 52px);
            letter-spacing: -.045em;
            margin-bottom: 12px
        }

        .field {
            display: grid;
            gap: 7px
        }

        .field label {
            font-size: 13px;
            font-weight: 800
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 13px;
            padding: 12px 14px;
            font: inherit;
            background: #fbfaf7
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: 2px solid var(--lime);
            border-color: var(--ink)
        }

        .fields-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px
        }

        .error {
            color: #b82e1a;
            font-size: 13px
        }

        .alert {
            padding: 14px 18px;
            border-radius: 14px;
            margin: 18px auto
        }

        .alert-success {
            background: #d9f6e6;
            color: #17603d
        }

        .alert-error {
            background: #ffe0da;
            color: #8f2715
        }

        .toast-container {
            position: fixed;
            top: 88px;
            right: 24px;
            z-index: 100;
            display: grid;
            gap: 10px;
            width: min(390px, calc(100vw - 32px));
            pointer-events: none
        }

        .toast {
            --toast-accent: var(--green);
            position: relative;
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr) 28px;
            align-items: start;
            gap: 11px;
            padding: 15px 14px;
            overflow: hidden;
            background: rgba(255, 255, 255, .97);
            color: var(--ink);
            border: 1px solid var(--line);
            border-left: 4px solid var(--toast-accent);
            border-radius: 16px;
            box-shadow: 0 18px 50px rgba(17, 20, 19, .2);
            pointer-events: auto;
            animation: toast-in .28s ease-out both
        }

        .toast-error {
            --toast-accent: var(--red)
        }

        .toast-icon {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            color: white;
            background: var(--toast-accent);
            border-radius: 50%;
            font-weight: 800
        }

        .toast-body {
            display: grid;
            gap: 2px;
            padding-top: 1px
        }

        .toast-body strong {
            font-size: 14px
        }

        .toast-body span {
            color: var(--muted);
            font-size: 13px;
            overflow-wrap: anywhere
        }

        .toast-close {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            padding: 0;
            color: var(--muted);
            background: transparent;
            border: 0;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer
        }

        .toast-close:hover {
            color: var(--ink);
            background: #eef0eb
        }

        .toast:after {
            content: "";
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            height: 3px;
            background: var(--toast-accent);
            transform-origin: left;
            animation: toast-life var(--toast-duration, 5s) linear forwards
        }

        .toast.is-leaving {
            animation: toast-out .22s ease-in forwards
        }

        .toast:hover:after {
            animation-play-state: paused
        }

        @keyframes toast-in {
            from {
                opacity: 0;
                transform: translate3d(30px, -8px, 0)
            }

            to {
                opacity: 1;
                transform: translate3d(0, 0, 0)
            }
        }

        @keyframes toast-out {
            to {
                opacity: 0;
                transform: translate3d(35px, 0, 0)
            }
        }

        @keyframes toast-life {
            to {
                transform: scaleX(0)
            }
        }

        .muted {
            color: var(--muted)
        }

        .divider {
            height: 1px;
            background: var(--line);
            margin: 22px 0
        }

        .empty {
            text-align: center;
            padding: 56px 20px;
            color: var(--muted)
        }

        .footer {
            border-top: 1px solid var(--line);
            padding: 34px 0;
            color: var(--muted);
            font-size: 14px
        }

        .wizard-progress {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px
        }

        .wizard-step {
            display: none
        }

        .wizard-step.is-active {
            display: grid
        }

        .category-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px
        }

        .category-option {
            position: relative;
            display: block;
            cursor: pointer
        }

        .category-option input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none
        }

        .category-option-card {
            position: relative;
            display: grid;
            min-height: 190px;
            align-content: space-between;
            gap: 16px;
            padding: 19px;
            overflow: hidden;
            background: #fbfaf7;
            border: 1.5px solid var(--line);
            border-radius: 20px;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease
        }

        .category-option:hover .category-option-card {
            transform: translateY(-2px);
            border-color: #9da294;
            box-shadow: 0 12px 30px rgba(17, 20, 19, .1)
        }

        .category-option input:checked+.category-option-card {
            background: linear-gradient(145deg, #f7ffe3, #fff);
            border-color: var(--ink);
            box-shadow: 0 0 0 3px var(--lime), 0 15px 35px rgba(17, 20, 19, .13)
        }

        .category-option input:focus-visible+.category-option-card {
            outline: 3px solid var(--lime);
            outline-offset: 3px
        }

        .category-check {
            position: absolute;
            top: 14px;
            right: 14px;
            display: grid;
            place-items: center;
            width: 27px;
            height: 27px;
            color: transparent;
            background: white;
            border: 1px solid var(--line);
            border-radius: 50%;
            font-size: 14px;
            font-weight: 800
        }

        .category-option input:checked+.category-option-card .category-check {
            color: var(--ink);
            background: var(--lime);
            border-color: var(--ink)
        }

        .category-name {
            display: block;
            max-width: calc(100% - 32px);
            font: 800 20px Manrope;
            line-height: 1.15
        }

        .category-distance {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 13px
        }

        .category-price {
            display: block;
            font: 800 23px Manrope;
            line-height: 1.1
        }

        .category-tier {
            color: var(--red);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase
        }

        .category-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px
        }

        .category-meta span {
            padding: 5px 8px;
            color: #4f554f;
            background: #eef0eb;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase
        }

        .wizard-dot {
            display: grid;
            gap: 4px;
            text-align: center;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700
        }

        .wizard-dot b {
            display: grid;
            place-items: center;
            width: 30px;
            height: 30px;
            margin: auto;
            border-radius: 50%;
            background: #e9ebe8
        }

        .wizard-dot.active {
            color: var(--ink)
        }

        .wizard-dot.active b {
            background: var(--lime)
        }

        .event-layout {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
            gap: 24px
        }

        .hero-with-banner {
            position: relative;
            min-height: clamp(390px, 42vw, 620px);
            display: flex;
            align-items: end;
            color: white;
            background-color: #111413;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover
        }

        .hero-with-banner:before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(17, 20, 19, .9) 0%, rgba(17, 20, 19, .55) 46%, rgba(17, 20, 19, .18) 100%);
            pointer-events: none
        }

        .hero-with-banner .wrap {
            position: relative;
            z-index: 1
        }

        .hero-with-banner p {
            color: #eef0eb
        }

        .event-description {
            color: var(--muted);
            overflow-wrap: anywhere
        }

        .event-description>*:first-child {
            margin-top: 0
        }

        .event-description>*:last-child {
            margin-bottom: 0
        }

        .event-description img {
            display: block;
            width: auto;
            max-width: 100%;
            height: auto;
            margin: 18px auto;
            border-radius: 16px
        }

        .banner-preview {
            display: grid;
            gap: 12px;
            padding: 14px;
            background: #f7f7f3;
            border: 1px solid var(--line);
            border-radius: 16px
        }

        .banner-preview img {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 12px
        }

        trix-editor {
            min-height: 220px;
            background: #fbfaf7;
            border: 1px solid var(--line);
            border-radius: 13px
        }

        trix-toolbar .trix-button-group {
            border-color: var(--line)
        }

        trix-editor:focus {
            outline: 2px solid var(--lime);
            border-color: var(--ink)
        }

        .countdown {
            width: min(690px, 100%);
            margin-top: 30px;
            padding: 18px;
            background: rgba(17, 20, 19, .94);
            color: white;
            border: 1px solid rgba(200, 255, 61, .45);
            border-radius: 24px;
            box-shadow: 0 20px 55px rgba(0, 0, 0, .2)
        }

        .countdown-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 14px
        }

        .countdown-head>div {
            display: grid;
            gap: 3px
        }

        .countdown-head strong {
            font: 800 18px Manrope
        }

        .countdown-head small {
            color: #d7dcd8;
            text-align: right
        }

        .countdown-kicker {
            color: var(--lime);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase
        }

        .countdown-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 9px
        }

        .countdown-unit {
            position: relative;
            display: grid;
            place-items: center;
            padding: 12px 8px;
            background: linear-gradient(145deg, rgba(255, 255, 255, .11), rgba(255, 255, 255, .04));
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 15px
        }

        .countdown-unit:not(:last-child):after {
            content: ":";
            position: absolute;
            right: -8px;
            top: 14px;
            color: var(--lime);
            font: 800 20px Manrope;
            z-index: 1
        }

        .countdown-unit strong {
            font: 800 clamp(25px, 4vw, 39px) Manrope;
            line-height: 1;
            color: var(--lime);
            font-variant-numeric: tabular-nums
        }

        .countdown-unit span {
            margin-top: 5px;
            color: #d7dcd8;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase
        }

        .admin-layout {
            display: grid;
            grid-template-columns: 230px 1fr;
            min-height: calc(100vh - 72px)
        }

        .sidebar {
            background: var(--ink);
            color: white;
            padding: 28px 20px
        }

        .sidebar a {
            display: block;
            padding: 11px 13px;
            border-radius: 10px;
            margin: 4px 0;
            color: #c6cac7
        }

        .sidebar a:hover {
            background: #272c29;
            color: white
        }

        .admin-main {
            padding: 36px
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin: 24px 0
        }

        .stat {
            background: white;
            border: 1px solid var(--line);
            padding: 22px;
            border-radius: 18px
        }

        .stat strong {
            display: block;
            font: 800 34px Manrope
        }

        .table-wrap {
            overflow-x: auto;
            background: white;
            border: 1px solid var(--line);
            border-radius: 18px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            text-align: left;
            padding: 14px 16px;
            border-bottom: 1px solid #ebe9e3;
            font-size: 14px
        }

        th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--muted)
        }

        .actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap
        }

        .detail-modal {
            position: fixed;
            inset: 0;
            width: min(860px, calc(100% - 32px));
            max-height: calc(100vh - 48px);
            margin: auto;
            padding: 0;
            color: var(--ink);
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 28px 90px rgba(17, 20, 19, .28)
        }

        .detail-modal::backdrop {
            background: rgba(17, 20, 19, .62);
            backdrop-filter: blur(4px)
        }

        .detail-modal-head {
            position: sticky;
            top: 0;
            z-index: 1;
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 18px;
            padding: 24px 26px 18px;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid var(--line)
        }

        .detail-modal-head h3 {
            margin-top: 3px;
            font-size: 25px
        }

        .modal-close {
            display: grid;
            flex: 0 0 38px;
            place-items: center;
            width: 38px;
            height: 38px;
            padding: 0;
            color: var(--ink);
            background: #f1f1ed;
            border: 0;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer
        }

        .detail-modal-body {
            display: grid;
            gap: 22px;
            padding: 24px 26px 30px
        }

        .detail-section {
            display: grid;
            gap: 12px
        }

        .detail-section h4 {
            margin: 0;
            padding-bottom: 8px;
            color: var(--red);
            border-bottom: 1px solid var(--line);
            font-size: 12px;
            letter-spacing: .11em;
            text-transform: uppercase
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 13px 24px
        }

        .detail-item {
            min-width: 0
        }

        .detail-item span {
            display: block;
            margin-bottom: 2px;
            color: var(--muted);
            font-size: 12px
        }

        .detail-item strong,
        .detail-item p {
            margin: 0;
            overflow-wrap: anywhere;
            font-size: 14px
        }

        .detail-item-wide {
            grid-column: 1 / -1
        }

        @media(max-width:800px) {
            .nav-links>a:not(.btn) {
                display: none
            }

            .hero {
                padding-top: 58px
            }

            .hero-with-banner {
                min-height: 390px;
                background-position: center top
            }

            .grid,
            .event-layout {
                grid-template-columns: 1fr
            }

            .fields-2,
            .stats {
                grid-template-columns: 1fr
            }

            .admin-layout {
                grid-template-columns: 1fr
            }

            .sidebar {
                display: flex;
                padding: 10px;
                gap: 5px;
                overflow: auto
            }

            .sidebar a {
                white-space: nowrap
            }

            .admin-main {
                padding: 24px 16px
            }

            .section {
                padding: 42px 0
            }

            .wizard-progress {
                gap: 3px
            }

            .wizard-dot {
                font-size: 9px
            }

            .category-options {
                grid-template-columns: 1fr
            }

            .category-option-card {
                min-height: 170px
            }

            .countdown {
                padding: 14px;
                border-radius: 19px
            }

            .countdown-head {
                display: grid;
                gap: 5px
            }

            .countdown-head small {
                text-align: left
            }

            .countdown-grid {
                gap: 6px
            }

            .countdown-unit {
                padding: 10px 3px
            }

            .countdown-unit:not(:last-child):after {
                right: -6px
            }

            .countdown-unit span {
                font-size: 8px;
                letter-spacing: .06em
            }

            .toast-container {
                top: 82px;
                right: 16px;
                left: 16px;
                width: auto
            }

            .detail-grid {
                grid-template-columns: 1fr
            }

            .detail-item-wide {
                grid-column: auto
            }

            .detail-modal-head,
            .detail-modal-body {
                padding-right: 18px;
                padding-left: 18px
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .toast,
            .toast:after,
            .toast.is-leaving {
                animation: none
            }
        }
    </style>
</head>

<body>
    <nav class="nav">
        <div class="wrap nav-inner">
            <a href="{{ route('home') }}" class="brand"><img src="{{ asset('favicon_abba.png') }}"
                    alt="Logo ABBA">ABBA</a>
            <div class="nav-links">
                <a href="{{ route('home') }}">Event</a>
                <a href="{{ route('registrations.lookup') }}">Cek Pendaftaran</a>
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                    <form method="post" action="{{ route('logout') }}">@csrf<button
                            class="btn btn-light btn-sm">Keluar</button></form>
                @else
                    <a class="btn btn-light btn-sm" href="{{ route('login') }}">Login</a>
                @endauth
            </div>
        </div>
    </nav>
    @if (session('success') || $errors->any())
        <div class="toast-container" aria-live="polite" aria-atomic="true">
            @if (session('success'))
                <div class="toast toast-success" role="status" data-toast data-duration="5000"
                    style="--toast-duration:5s">
                    <span class="toast-icon" aria-hidden="true">✓</span>
                    <div class="toast-body"><strong>Berhasil</strong><span>{{ session('success') }}</span></div>
                    <button class="toast-close" type="button" aria-label="Tutup notifikasi" data-toast-close>×</button>
                </div>
            @endif
            @if ($errors->any())
                <div class="toast toast-error" role="alert" data-toast data-duration="8000"
                    style="--toast-duration:8s">
                    <span class="toast-icon" aria-hidden="true">!</span>
                    <div class="toast-body"><strong>Ada data yang perlu
                            diperbaiki</strong><span>{{ $errors->first() }}</span></div>
                    <button class="toast-close" type="button" aria-label="Tutup notifikasi" data-toast-close>×</button>
                </div>
            @endif
        </div>
    @endif
    <main>@yield('content')</main>
    <footer class="footer">
        <div class="wrap row"><span>© {{ date('Y') }} ABBA.</span><span>Dibuat untuk pelari, oleh
                penyelenggara.</span></div>
    </footer>
    <script>
        const bindToast = toast => {
            let timeout;
            const dismiss = () => {
                if (toast.classList.contains('is-leaving')) return;
                toast.classList.add('is-leaving');
                setTimeout(() => toast.remove(), 230)
            };
            const start = () => {
                timeout = setTimeout(dismiss, Number(toast.dataset.duration) || 5000)
            };
            toast.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
            toast.addEventListener('mouseenter', () => clearTimeout(timeout));
            toast.addEventListener('mouseleave', start);
            start()
        };
        document.querySelectorAll('[data-toast]').forEach(bindToast);
        window.addEventListener('app:toast', event => {
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                container.setAttribute('aria-live', 'polite');
                document.body.appendChild(container)
            }
            const isError = event.detail?.type === 'error';
            const toast = document.createElement('div');
            toast.className = `toast ${isError?'toast-error':'toast-success'}`;
            toast.dataset.toast = '';
            toast.dataset.duration = isError ? '8000' : '5000';
            toast.style.setProperty('--toast-duration', isError ? '8s' : '5s');
            const icon = document.createElement('span');
            icon.className = 'toast-icon';
            icon.textContent = isError ? '!' : '✓';
            const body = document.createElement('div');
            body.className = 'toast-body';
            const title = document.createElement('strong');
            title.textContent = isError ? 'Terjadi kesalahan' : 'Berhasil';
            const message = document.createElement('span');
            message.textContent = event.detail?.message || 'Proses selesai.';
            const close = document.createElement('button');
            close.className = 'toast-close';
            close.type = 'button';
            close.dataset.toastClose = '';
            close.setAttribute('aria-label', 'Tutup notifikasi');
            close.textContent = '×';
            body.append(title, message);
            toast.append(icon, body, close);
            container.appendChild(toast);
            bindToast(toast)
        });
    </script>
</body>

</html>
