<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliotheque Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%93%9A%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: #080b16;
            --bg-soft: #0e1326;
            --panel: rgba(18, 25, 45, 0.72);
            --panel-strong: rgba(14, 19, 35, 0.9);
            --card: rgba(17, 24, 42, 0.78);
            --card-hover: rgba(23, 33, 58, 0.9);
            --text: #f7f9ff;
            --text-soft: #a9b4d0;
            --border: rgba(136, 157, 201, 0.2);
            --border-strong: rgba(136, 157, 201, 0.32);
            --primary: #4f7cff;
            --primary-soft: rgba(79, 124, 255, 0.2);
            --accent: #7f5bff;
            --success: #37d6a9;
            --danger: #ff6f91;
            --shadow: 0 16px 34px rgba(0, 0, 0, 0.35);
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 10px;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: "Plus Jakarta Sans", sans-serif;
        }

        body {
            overflow-x: hidden;
            background-image:
                radial-gradient(circle at 12% 10%, rgba(79, 124, 255, 0.28), transparent 35%),
                radial-gradient(circle at 84% 0%, rgba(127, 91, 255, 0.2), transparent 30%),
                linear-gradient(180deg, #090d1b 0%, #070a14 100%);
        }

        body.theme-light {
            --bg: #f3f6ff;
            --bg-soft: #e9eefc;
            --panel: rgba(255, 255, 255, 0.88);
            --panel-strong: rgba(255, 255, 255, 0.98);
            --card: rgba(255, 255, 255, 0.94);
            --card-hover: rgba(255, 255, 255, 1);
            --text: #1a2740;
            --text-soft: #5b6c90;
            --border: rgba(104, 122, 164, 0.25);
            --border-strong: rgba(104, 122, 164, 0.4);
            --primary-soft: rgba(79, 124, 255, 0.15);
            --shadow: 0 12px 24px rgba(43, 66, 112, 0.12);
            background-image:
                radial-gradient(circle at 8% 8%, rgba(79, 124, 255, 0.16), transparent 32%),
                radial-gradient(circle at 90% 4%, rgba(127, 91, 255, 0.12), transparent 28%),
                linear-gradient(180deg, #f7f9ff 0%, #eef3ff 100%);
        }

        .app-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .app-header {
            position: sticky;
            top: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 1rem;
            background: rgba(8, 11, 22, 0.72);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
        }

        body.theme-light .app-header {
            background: rgba(247, 250, 255, 0.88);
        }

        .header-left,
        .header-right {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .brand-emoji {
            font-size: 1.5rem;
            line-height: 1;
        }

        .brand-title {
            margin: 0;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .brand-sub {
            margin: 0;
            color: var(--text-soft);
            font-size: 0.8rem;
        }

        .db-total-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0.45rem 0.75rem;
            background: rgba(16, 23, 40, 0.7);
            color: var(--text-soft);
            font-size: 0.86rem;
            white-space: nowrap;
        }

        body.theme-light .db-total-badge {
            background: rgba(241, 246, 255, 0.95);
        }

        .db-total-badge svg {
            width: 15px;
            height: 15px;
            color: var(--primary);
        }

        .export-btn,
        .icon-btn,
        .page-btn,
        .reset-btn,
        .order-btn,
        .lang-btn,
        select,
        input {
            font-family: inherit;
        }

        .export-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            border: 1px solid rgba(118, 146, 219, 0.45);
            background: linear-gradient(135deg, rgba(79, 124, 255, 0.28), rgba(127, 91, 255, 0.22));
            color: #f2f6ff;
            border-radius: 10px;
            padding: 0.55rem 0.9rem;
            cursor: pointer;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        body.theme-light .export-btn {
            color: #243b67;
            border-color: rgba(97, 123, 182, 0.45);
            background: linear-gradient(135deg, rgba(79, 124, 255, 0.14), rgba(127, 91, 255, 0.1));
        }

        .export-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(154, 177, 241, 0.7);
            box-shadow: 0 10px 20px rgba(58, 91, 187, 0.28);
        }

        .export-btn svg {
            width: 16px;
            height: 16px;
        }

        .icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: rgba(15, 21, 39, 0.85);
            color: var(--text-soft);
            cursor: pointer;
            transition: border-color 0.2s ease, color 0.2s ease, background 0.2s ease;
        }

        body.theme-light .icon-btn,
        body.theme-light .order-btn,
        body.theme-light .page-btn,
        body.theme-light .lang-btn,
        body.theme-light .reset-btn,
        body.theme-light input,
        body.theme-light select {
            background: rgba(255, 255, 255, 0.95);
            color: var(--text);
        }

        .icon-btn:hover {
            color: var(--text);
            border-color: var(--border-strong);
            background: rgba(20, 28, 50, 0.95);
        }

        .icon-btn svg {
            width: 18px;
            height: 18px;
        }

        .layout {
            flex: 1;
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 1rem;
            padding: 1rem;
            width: 100%;
            transition: grid-template-columns 0.25s ease, gap 0.25s ease;
        }

        .layout.sidebar-collapsed {
            grid-template-columns: 0 minmax(0, 1fr);
            gap: 0;
        }

        .sidebar {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
            padding: 1rem;
            height: calc(100vh - 5.2rem);
            position: sticky;
            top: 4.2rem;
            overflow: auto;
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        .layout.sidebar-collapsed .sidebar {
            transform: translateX(-110%);
            opacity: 0;
            pointer-events: none;
        }

        body.theme-light .sidebar {
            box-shadow: 0 12px 26px rgba(50, 76, 128, 0.12);
        }

        .sidebar-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .sidebar-title {
            margin: 0;
            font-size: 1rem;
            letter-spacing: 0.01em;
        }

        .filter-group {
            margin-bottom: 1rem;
        }

        .filter-group > label,
        .group-label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.8rem;
            color: var(--text-soft);
            letter-spacing: 0.02em;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            width: 16px;
            height: 16px;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8eb0;
        }

        body.theme-light .input-wrap svg {
            color: #6780b1;
        }

        input,
        select {
            width: 100%;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: rgba(13, 19, 35, 0.95);
            color: var(--text);
            padding: 0.62rem 0.7rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: rgba(110, 147, 255, 0.8);
            box-shadow: 0 0 0 3px var(--primary-soft);
        }

        #searchInput {
            padding-left: 2rem;
        }

        .language-buttons {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.45rem;
        }

        .lang-btn {
            border: 1px solid var(--border);
            background: rgba(12, 17, 32, 0.92);
            border-radius: 10px;
            color: var(--text-soft);
            padding: 0.5rem 0.35rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
        }

        .lang-btn:hover {
            border-color: var(--border-strong);
            color: var(--text);
        }

        .lang-btn.active {
            background: linear-gradient(140deg, rgba(79, 124, 255, 0.28), rgba(127, 91, 255, 0.18));
            border-color: rgba(129, 163, 255, 0.8);
            color: #f2f6ff;
            box-shadow: inset 0 0 0 1px rgba(145, 176, 255, 0.25);
        }

        body.theme-light .lang-btn.active {
            color: #244177;
            border-color: rgba(95, 127, 199, 0.62);
            background: linear-gradient(140deg, rgba(79, 124, 255, 0.18), rgba(127, 91, 255, 0.12));
        }

        .year-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.45rem;
        }

        .year-grid label {
            display: block;
            font-size: 0.74rem;
            color: #94a4c9;
            margin-bottom: 0.2rem;
        }

        body.theme-light .year-grid label {
            color: #5f78a9;
        }

        .year-hint {
            margin-top: 0.4rem;
            color: #8ea0ca;
            font-size: 0.75rem;
        }

        body.theme-light .year-hint,
        body.theme-light .group-label,
        body.theme-light .filter-group > label {
            color: #6176a3;
        }

        .sort-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.45rem;
            align-items: center;
        }

        .order-btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(13, 19, 35, 0.95);
            color: var(--text);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.62rem 0.7rem;
            min-width: 92px;
            transition: border-color 0.2s ease, transform 0.2s ease;
        }

        .order-btn:hover {
            border-color: var(--border-strong);
            transform: translateY(-1px);
        }

        .reset-btn {
            width: 100%;
            margin-top: 0.4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            border: 1px dashed rgba(255, 255, 255, 0.28);
            border-radius: 10px;
            background: rgba(12, 17, 30, 0.75);
            color: #d3dcf3;
            padding: 0.66rem 0.8rem;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        body.theme-light .reset-btn {
            color: #35517f;
            border-color: rgba(100, 122, 171, 0.45);
            background: rgba(255, 255, 255, 0.95);
        }

        .reset-btn:hover {
            border-color: rgba(255, 255, 255, 0.46);
            background: rgba(18, 25, 43, 0.95);
        }

        body.theme-light .reset-btn:hover {
            border-color: rgba(88, 113, 166, 0.55);
            background: rgba(236, 243, 255, 0.96);
        }

        .reset-btn svg {
            width: 16px;
            height: 16px;
        }

        .main-content {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
        }

        .stats-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.65rem;
        }

        .stat-chip {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 0.75rem 0.8rem;
            min-height: 72px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: border-color 0.2s ease, transform 0.2s ease;
        }

        .stat-chip:hover {
            border-color: var(--border-strong);
            transform: translateY(-1px);
        }

        .stat-chip.primary {
            border-color: var(--border);
            background: var(--panel);
        }

        body.theme-light .stat-chip.primary {
            background: var(--panel);
        }

        .chip-label {
            color: #8fa2cb;
            font-size: 0.76rem;
            margin-bottom: 0.32rem;
        }

        body.theme-light .chip-label {
            color: #5f74a1;
        }

        .chip-value {
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.2;
            word-break: break-word;
        }

        .results-meta {
            color: var(--text-soft);
            font-size: 0.86rem;
            min-height: 20px;
        }

        body.theme-light .results-meta,
        body.theme-light .meta-muted {
            color: #5d739f;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.65rem;
        }

        .book-card {
            position: relative;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 0.72rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            min-height: 236px;
            transition: transform 0.24s ease, border-color 0.24s ease, background 0.24s ease;
            overflow: hidden;
        }

        .book-card::before {
            content: "";
            position: absolute;
            inset: -40% auto auto -25%;
            width: 130px;
            height: 130px;
            background: radial-gradient(circle, rgba(79, 124, 255, 0.14), transparent 70%);
            pointer-events: none;
        }

        body.theme-light .book-card::before {
            background: radial-gradient(circle, rgba(79, 124, 255, 0.2), transparent 72%);
        }

        .book-card:hover {
            transform: translateY(-4px);
            border-color: rgba(146, 173, 238, 0.46);
            background: var(--card-hover);
        }

        .book-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.42rem;
        }

        .inventory-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 58px;
            max-width: 130px;
            flex-shrink: 0;
            padding: 0.16rem 0.45rem;
            border-radius: 999px;
            border: 1px solid rgba(79, 124, 255, 0.38);
            background: rgba(79, 124, 255, 0.16);
            color: #dce8ff;
            font-size: 0.72rem;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cote-badge,
        .lang-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.28rem;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            border: 1px solid rgba(136, 157, 201, 0.3);
            background: rgba(15, 21, 38, 0.85);
            font-size: 0.72rem;
            color: #c8d3ef;
            white-space: nowrap;
        }

        .lang-badge {
            border-color: rgba(127, 91, 255, 0.45);
            color: #d8ceff;
        }

        body.theme-light .cote-badge,
        body.theme-light .lang-badge {
            background: rgba(244, 247, 255, 0.95);
            color: #48629b;
        }

        body.theme-light .lang-badge {
            color: #674daf;
        }

        body.theme-light .inventory-badge {
            color: #254a9d;
            background: rgba(79, 124, 255, 0.12);
        }

        body.theme-light .book-inline-item,
        body.theme-light .book-author svg,
        body.theme-light .book-inline-item svg {
            color: #6078ac;
        }

        body.theme-light .details-btn {
            color: #1f2d4c;
            border-color: rgba(110, 91, 205, 0.45);
            background: linear-gradient(130deg, rgba(127, 91, 255, 0.12), rgba(79, 124, 255, 0.1));
        }

        .book-title {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.3;
            font-weight: 700;
            color: #f7faff;
            min-height: 2.5em;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        body.theme-light .book-title {
            color: #1f3155;
        }

        .book-author {
            margin: 0;
            color: var(--text-soft);
            font-size: 0.86rem;
            min-height: 1.2em;
            display: flex;
            align-items: center;
            gap: 0.28rem;
            overflow: hidden;
        }

        .book-author span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .book-author svg {
            width: 14px;
            height: 14px;
            color: #88a0d2;
            flex-shrink: 0;
        }

        .book-meta-list {
            display: flex;
            flex-direction: column;
            gap: 0.22rem;
            margin-top: 0.15rem;
        }

        .book-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.34rem 0.45rem;
            margin-top: 0.05rem;
        }

        .book-inline-item {
            display: inline-flex;
            align-items: center;
            gap: 0.26rem;
            color: #9caed3;
            font-size: 0.75rem;
            min-width: 0;
            overflow: hidden;
        }

        .book-inline-item span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .book-inline-item svg {
            width: 14px;
            height: 14px;
            color: #89a0d0;
            flex-shrink: 0;
        }

        .book-meta-item {
            color: #96a7cd;
            font-size: 0.77rem;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        body.theme-light .book-meta-item {
            color: #5c709b;
        }

        .book-meta-item strong {
            color: #c9d5f2;
            font-weight: 600;
        }

        body.theme-light .book-meta-item strong {
            color: #31496f;
        }

        .book-bottom {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            flex-wrap: wrap;
            padding-top: 0.45rem;
            border-top: 1px dashed rgba(136, 157, 201, 0.18);
        }

        body.theme-light .book-bottom {
            border-top-color: rgba(101, 123, 168, 0.24);
        }

        .book-footer-left {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
            min-width: 0;
        }

        .book-actions {
            display: flex;
            justify-content: flex-end;
        }

        .details-btn {
            border: 1px solid rgba(127, 91, 255, 0.55);
            background: linear-gradient(130deg, rgba(127, 91, 255, 0.22), rgba(79, 124, 255, 0.2));
            color: #efe8ff;
            border-radius: 9px;
            padding: 0.38rem 0.62rem;
            font-size: 0.77rem;
            cursor: pointer;
            transition: border-color 0.2s ease, transform 0.2s ease, background 0.2s ease;
        }

        .details-btn svg {
            width: 14px;
            height: 14px;
            margin-right: 0.22rem;
            vertical-align: text-bottom;
        }

        .details-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(165, 134, 255, 0.75);
            background: linear-gradient(130deg, rgba(127, 91, 255, 0.3), rgba(79, 124, 255, 0.28));
        }

        .details-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(127, 91, 255, 0.3);
        }

        .meta-muted {
            color: #9aaace;
            font-size: 0.78rem;
            white-space: nowrap;
        }

        .matiere-chip {
            max-width: 100%;
            border: 1px solid rgba(79, 124, 255, 0.42);
            background: rgba(79, 124, 255, 0.15);
            color: #dbe7ff;
            border-radius: 999px;
            padding: 0.18rem 0.5rem;
            font-size: 0.73rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        body.theme-light .matiere-chip {
            color: #2e4d87;
            background: rgba(79, 124, 255, 0.12);
        }

        .rtl-text {
            direction: rtl;
            text-align: right;
        }

        .pagination {
            margin-top: 0.25rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.7rem;
            padding-bottom: 0.35rem;
        }

        .page-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: 1px solid var(--border);
            background: rgba(13, 18, 31, 0.88);
            color: var(--text);
            border-radius: 10px;
            padding: 0.48rem 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .page-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .page-btn:not(:disabled):hover {
            border-color: var(--border-strong);
            transform: translateY(-1px);
            background: rgba(20, 28, 50, 0.95);
        }

        body.theme-light .page-btn:not(:disabled):hover {
            background: rgba(236, 242, 255, 0.96);
        }

        .page-btn svg {
            width: 16px;
            height: 16px;
        }

        .page-info {
            color: var(--text-soft);
            font-size: 0.88rem;
            min-width: 130px;
            text-align: center;
        }

        .detailed-stats {
            margin-top: 0.4rem;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(12, 18, 33, 0.72);
            padding: 0.85rem;
        }

        body.theme-light .detailed-stats {
            background: rgba(255, 255, 255, 0.9);
        }

        .detailed-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.8rem;
            margin-bottom: 0.75rem;
        }

        .detailed-title {
            margin: 0;
            font-size: 0.98rem;
            font-weight: 700;
        }

        .load-charts-btn {
            border: 1px solid rgba(118, 146, 219, 0.45);
            background: linear-gradient(135deg, rgba(79, 124, 255, 0.24), rgba(127, 91, 255, 0.2));
            color: #eff4ff;
            border-radius: 10px;
            padding: 0.5rem 0.72rem;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        body.theme-light .load-charts-btn {
            color: #29406c;
            border-color: rgba(97, 123, 182, 0.45);
            background: linear-gradient(135deg, rgba(79, 124, 255, 0.14), rgba(127, 91, 255, 0.1));
        }

        .load-charts-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(154, 177, 241, 0.7);
        }

        .load-charts-btn svg {
            width: 15px;
            height: 15px;
        }

        .detailed-counters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .detailed-counter {
            border: 1px solid rgba(126, 145, 181, 0.25);
            border-radius: 10px;
            background: rgba(10, 15, 29, 0.82);
            padding: 0.6rem 0.65rem;
        }

        body.theme-light .detailed-counter,
        body.theme-light .chart-card,
        body.theme-light .modal-item {
            background: rgba(248, 251, 255, 0.97);
        }

        .detailed-counter .label {
            display: block;
            color: #90a2cb;
            font-size: 0.73rem;
            margin-bottom: 0.24rem;
        }

        .detailed-counter .value {
            color: #f5f8ff;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.2;
            word-break: break-word;
        }

        body.theme-light .detailed-counter .value {
            color: #233757;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
        }

        .chart-card {
            border: 1px solid rgba(126, 145, 181, 0.24);
            border-radius: 11px;
            background: rgba(11, 17, 31, 0.85);
            padding: 0.65rem;
        }

        .chart-title {
            margin: 0 0 0.55rem;
            color: #c9d7f5;
            font-size: 0.84rem;
            font-weight: 600;
        }

        body.theme-light .chart-title {
            color: #3f5687;
        }

        .bar-chart-list {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            max-height: 290px;
            overflow: auto;
            padding-right: 0.15rem;
        }

        .bar-row {
            display: grid;
            grid-template-columns: minmax(72px, 130px) 1fr auto;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.74rem;
            color: #c9d6f2;
        }

        .bar-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .bar-track {
            width: 100%;
            height: 8px;
            border-radius: 999px;
            background: rgba(126, 145, 181, 0.2);
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, rgba(79, 124, 255, 0.9), rgba(127, 91, 255, 0.9));
        }

        .bar-count {
            color: #9fb1d8;
            min-width: 38px;
            text-align: right;
        }

        .line-chart-wrap {
            height: 290px;
            position: relative;
            border: 1px solid rgba(126, 145, 181, 0.2);
            border-radius: 10px;
            background: rgba(8, 12, 25, 0.8);
            overflow: hidden;
        }

        body.theme-light .line-chart-wrap {
            background: rgba(255, 255, 255, 0.95);
        }

        .line-chart-wrap svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .line-chart-wrap canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }

        .chart-empty {
            color: #90a2cb;
            font-size: 0.82rem;
            text-align: center;
            padding: 2rem 0.5rem;
        }

        .empty-state {
            border: 1px dashed rgba(138, 155, 191, 0.35);
            border-radius: 14px;
            background: rgba(12, 17, 31, 0.7);
            text-align: center;
            padding: 2rem 1rem;
        }

        body.theme-light .empty-state {
            background: rgba(255, 255, 255, 0.92);
            border-color: rgba(103, 124, 171, 0.3);
        }

        .empty-state h3 {
            margin: 0.55rem 0 0.25rem;
            font-size: 1rem;
        }

        .empty-state p {
            margin: 0;
            color: var(--text-soft);
            font-size: 0.88rem;
        }

        .empty-icon {
            font-size: 2rem;
            line-height: 1;
        }

        .hidden {
            display: none !important;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(4, 8, 16, 0.66);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 90;
        }

        .modal-card {
            width: min(780px, 100%);
            border: 1px solid var(--border-strong);
            background: var(--panel-strong);
            border-radius: 16px;
            box-shadow: 0 26px 48px rgba(0, 0, 0, 0.45);
            overflow: hidden;
        }

        body.theme-light .modal-overlay {
            background: rgba(18, 35, 74, 0.28);
        }

        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.8rem;
            padding: 0.9rem 1rem;
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            margin: 0;
            font-size: 1.02rem;
            font-weight: 700;
        }

        .modal-close {
            border: 1px solid var(--border);
            background: rgba(16, 22, 38, 0.8);
            color: var(--text-soft);
            border-radius: 10px;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        body.theme-light .modal-close {
            background: rgba(255, 255, 255, 0.95);
            color: #5c729f;
        }

        .modal-close:hover {
            color: var(--text);
            border-color: var(--border-strong);
        }

        .modal-close svg {
            width: 16px;
            height: 16px;
        }

        .modal-body {
            padding: 1rem;
        }

        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
        }

        .modal-item {
            border: 1px solid rgba(126, 145, 181, 0.25);
            border-radius: 10px;
            background: rgba(12, 18, 34, 0.86);
            padding: 0.65rem 0.75rem;
        }

        .modal-label {
            display: block;
            color: #91a1c8;
            font-size: 0.73rem;
            margin-bottom: 0.26rem;
        }

        body.theme-light .modal-label {
            color: #6078a9;
        }

        .modal-value {
            color: #f5f8ff;
            font-size: 0.9rem;
            line-height: 1.35;
            word-break: break-word;
        }

        body.theme-light .modal-value {
            color: #233b61;
        }

        .skeleton-card {
            background: rgba(13, 18, 33, 0.9);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 0.8rem;
            min-height: 178px;
            overflow: hidden;
            position: relative;
        }

        body.theme-light .skeleton-card {
            background: rgba(255, 255, 255, 0.95);
        }

        .skeleton-line {
            border-radius: 8px;
            height: 10px;
            margin-bottom: 0.45rem;
            background: linear-gradient(90deg, rgba(95, 110, 145, 0.15) 25%, rgba(139, 157, 198, 0.35) 50%, rgba(95, 110, 145, 0.15) 75%);
            background-size: 220% 100%;
            animation: shimmer 1.2s linear infinite;
        }

        body.theme-light .skeleton-line {
            background: linear-gradient(90deg, rgba(188, 203, 236, 0.3) 25%, rgba(131, 156, 212, 0.5) 50%, rgba(188, 203, 236, 0.3) 75%);
        }

        .skeleton-line.short { width: 38%; }
        .skeleton-line.medium { width: 62%; }
        .skeleton-line.long { width: 90%; }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .sidebar-overlay {
            display: none;
        }

        .mobile-only {
            display: none;
        }

        @media (max-width: 1160px) {
            .stats-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .results-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .detailed-counters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 980px) {
            .mobile-only {
                display: inline-flex;
            }

            .layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: min(86vw, 320px);
                border-radius: 0 16px 16px 0;
                transform: translateX(-108%);
                transition: transform 0.25s ease;
                z-index: 80;
                padding-top: 1.2rem;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(2, 6, 14, 0.55);
                z-index: 70;
                backdrop-filter: blur(2px);
            }

            .sidebar-overlay.visible {
                display: block;
            }

            .app-header {
                padding: 0.7rem 0.8rem;
            }

            .db-total-badge {
                display: none;
            }

            .export-btn {
                padding: 0.5rem 0.68rem;
                font-size: 0.83rem;
            }
        }

        @media (max-width: 620px) {
            .stats-strip {
                grid-template-columns: 1fr;
            }

            .results-grid {
                grid-template-columns: 1fr;
            }

            .brand-sub {
                display: none;
            }

            .detailed-head {
                flex-direction: column;
                align-items: stretch;
            }

            .detailed-counters {
                grid-template-columns: 1fr;
            }

            .bar-row {
                grid-template-columns: minmax(62px, 100px) 1fr auto;
            }

            .modal-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <header class="app-header">
            <div class="header-left">
                <button id="openSidebarBtn" class="icon-btn" aria-label="Open filters">
                    <i data-lucide="panel-left"></i>
                </button>
                <div class="brand">
                    <span class="brand-emoji">📚</span>
                    <div>
                        <p class="brand-title">Bibliothèque</p>
                        <p class="brand-sub">Dashboard PMB</p>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="db-total-badge">
                    <i data-lucide="database"></i>
                    <span id="dbTotalCount">Chargement...</span>
                </div>
                <button id="themeToggleBtn" class="icon-btn" aria-label="Changer le thème">
                    <i data-lucide="sun"></i>
                </button>
                <button id="exportBtn" class="export-btn">
                    <i data-lucide="download"></i>
                    Export CSV
                </button>
            </div>
        </header>

        <div id="mainLayout" class="layout">
            <aside id="sidebar" class="sidebar" aria-label="Filters">
                <div class="sidebar-head">
                    <h2 class="sidebar-title">Filtres live</h2>
                    <button id="closeSidebarBtn" class="icon-btn" aria-label="Close filters">
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <div class="filter-group">
                    <label for="searchInput">Recherche</label>
                    <div class="input-wrap">
                        <i data-lucide="search"></i>
                        <input type="text" id="searchInput" placeholder="Titre, auteur, cote..." autocomplete="off">
                    </div>
                </div>

                <div class="filter-group">
                    <span class="group-label">Langue</span>
                    <div id="languageButtons" class="language-buttons">
                        <button type="button" class="lang-btn" data-lang="fr">French</button>
                        <button type="button" class="lang-btn" data-lang="en">English</button>
                        <button type="button" class="lang-btn" data-lang="ar">Arabic</button>
                    </div>
                </div>

                <div class="filter-group">
                    <label for="matiereSelect">Matiere</label>
                    <select id="matiereSelect">
                        <option value="">Toutes les matieres</option>
                    </select>
                </div>

                <div class="filter-group">
                    <span class="group-label">Annee de publication</span>
                    <div class="year-grid">
                        <div>
                            <label for="yearMin">Min</label>
                            <input id="yearMin" type="number" inputmode="numeric" placeholder="Min">
                        </div>
                        <div>
                            <label for="yearMax">Max</label>
                            <input id="yearMax" type="number" inputmode="numeric" placeholder="Max">
                        </div>
                    </div>
                    <div id="yearHint" class="year-hint">Plage: --</div>
                </div>

                <div class="filter-group">
                    <label for="sortField">Trier par</label>
                    <div class="sort-row">
                        <select id="sortField">
                            <option value="titre">Titre</option>
                            <option value="auteur">Auteur</option>
                            <option value="annee">Annee</option>
                        </select>
                        <button id="orderToggle" type="button" class="order-btn">
                            <span id="orderArrow">↑</span>
                            <span id="orderLabel">ASC</span>
                        </button>
                    </div>
                </div>

                <button id="resetBtn" type="button" class="reset-btn">
                    <i data-lucide="rotate-ccw"></i>
                    Reset filters
                </button>
            </aside>

            <div id="sidebarOverlay" class="sidebar-overlay"></div>

            <main class="main-content">
                <section class="stats-strip">
                    <article class="stat-chip primary">
                        <span class="chip-label">Resultats courants</span>
                        <span id="queryTotalChip" class="chip-value">0</span>
                    </article>
                    <article class="stat-chip">
                        <span class="chip-label">Total livres (DB)</span>
                        <span id="totalBooksChip" class="chip-value">--</span>
                    </article>
                    <article class="stat-chip">
                        <span class="chip-label">Top matiere</span>
                        <span id="topMatiereChip" class="chip-value">--</span>
                    </article>
                    <article class="stat-chip">
                        <span class="chip-label">Range annee</span>
                        <span id="yearRangeChip" class="chip-value">--</span>
                    </article>
                </section>

                <div id="resultsMeta" class="results-meta"></div>

                <section id="skeletonGrid" class="results-grid"></section>
                <section id="resultsGrid" class="results-grid hidden" aria-live="polite"></section>

                <section id="emptyState" class="empty-state hidden">
                    <div class="empty-icon">📭</div>
                    <h3>Aucun resultat</h3>
                    <p>Essayez une autre combinaison de filtres.</p>
                </section>

                <div class="pagination">
                    <button id="prevBtn" type="button" class="page-btn">
                        <i data-lucide="chevron-left"></i>
                        Prev
                    </button>
                    <div id="pageInfo" class="page-info">Page 1 sur 1</div>
                    <button id="nextBtn" type="button" class="page-btn">
                        Next
                        <i data-lucide="chevron-right"></i>
                    </button>
                </div>

                <section class="detailed-stats">
                    <div class="detailed-head">
                        <h3 class="detailed-title">Statistiques détaillées</h3>
                        <button id="loadChartsBtn" type="button" class="load-charts-btn">
                            Charger les graphiques détaillés
                        </button>
                    </div>

                    <div class="detailed-counters">
                        <article class="detailed-counter">
                            <span class="label">Résultats courants</span>
                            <span id="detailedQueryTotal" class="value">0</span>
                        </article>
                        <article class="detailed-counter">
                            <span class="label">Total livres (DB)</span>
                            <span id="detailedTotalBooks" class="value">--</span>
                        </article>
                        <article class="detailed-counter">
                            <span class="label">Top matière</span>
                            <span id="detailedTopMatiere" class="value">--</span>
                        </article>
                        <article class="detailed-counter">
                            <span class="label">Plage années</span>
                            <span id="detailedYearRange" class="value">--</span>
                        </article>
                    </div>

                    <div id="chartsWrap" class="charts-grid hidden">
                        <article class="chart-card">
                            <h4 class="chart-title">Graphe des matières (barres)</h4>
                            <div class="line-chart-wrap">
                                <canvas id="matieresChart"></canvas>
                            </div>
                        </article>
                        <article class="chart-card">
                            <h4 class="chart-title">Graphe des années (courbe)</h4>
                            <div class="line-chart-wrap">
                                <canvas id="anneesChart"></canvas>
                            </div>
                        </article>
                    </div>
                </section>
            </main>

            <div id="detailsModal" class="modal-overlay hidden" aria-hidden="true" role="dialog" aria-modal="true">
                <div class="modal-card">
                    <div class="modal-head">
                        <h3 class="modal-title">Détails du livre</h3>
                        <button id="closeModalBtn" class="modal-close" type="button" aria-label="Fermer">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-grid">
                            <div class="modal-item"><span class="modal-label">Titre</span><div id="modalTitre" class="modal-value">-</div></div>
                            <div class="modal-item"><span class="modal-label">Auteur</span><div id="modalAuteur" class="modal-value">-</div></div>
                            <div class="modal-item"><span class="modal-label">Cote</span><div id="modalCote" class="modal-value">-</div></div>
                            <div class="modal-item"><span class="modal-label">Inventaire</span><div id="modalInventaire" class="modal-value">-</div></div>
                            <div class="modal-item"><span class="modal-label">Matiere</span><div id="modalMatiere" class="modal-value">-</div></div>
                            <div class="modal-item"><span class="modal-label">Langue</span><div id="modalLangue" class="modal-value">-</div></div>
                            <div class="modal-item"><span class="modal-label">Annee</span><div id="modalAnnee" class="modal-value">-</div></div>
                            <div class="modal-item"><span class="modal-label">Nombre de pages</span><div id="modalPages" class="modal-value">-</div></div>
                            <div class="modal-item"><span class="modal-label">Edition</span><div id="modalEdition" class="modal-value">-</div></div>
                            <div class="modal-item"><span class="modal-label">Lieu</span><div id="modalLieu" class="modal-value">-</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const DEFAULT_STATE = {
            q: "",
            langue: "",
            matiere: "",
            annee_min: "",
            annee_max: "",
            sort: "titre",
            order: "ASC",
            page: 1,
            per_page: 12,
            total_pages: 1
        };

        const state = { ...DEFAULT_STATE };
        let statsCache = null;
        let activeController = null;
        let currentResults = [];
        const chartInstances = { matieres: null, annees: null };

        const refs = {
            layout: document.getElementById("mainLayout"),
            sidebar: document.getElementById("sidebar"),
            sidebarOverlay: document.getElementById("sidebarOverlay"),
            openSidebarBtn: document.getElementById("openSidebarBtn"),
            closeSidebarBtn: document.getElementById("closeSidebarBtn"),
            searchInput: document.getElementById("searchInput"),
            languageButtons: document.getElementById("languageButtons"),
            matiereSelect: document.getElementById("matiereSelect"),
            yearMin: document.getElementById("yearMin"),
            yearMax: document.getElementById("yearMax"),
            yearHint: document.getElementById("yearHint"),
            sortField: document.getElementById("sortField"),
            orderToggle: document.getElementById("orderToggle"),
            orderArrow: document.getElementById("orderArrow"),
            orderLabel: document.getElementById("orderLabel"),
            resetBtn: document.getElementById("resetBtn"),
            themeToggleBtn: document.getElementById("themeToggleBtn"),
            exportBtn: document.getElementById("exportBtn"),
            dbTotalCount: document.getElementById("dbTotalCount"),
            queryTotalChip: document.getElementById("queryTotalChip"),
            totalBooksChip: document.getElementById("totalBooksChip"),
            topMatiereChip: document.getElementById("topMatiereChip"),
            yearRangeChip: document.getElementById("yearRangeChip"),
            resultsMeta: document.getElementById("resultsMeta"),
            skeletonGrid: document.getElementById("skeletonGrid"),
            resultsGrid: document.getElementById("resultsGrid"),
            emptyState: document.getElementById("emptyState"),
            prevBtn: document.getElementById("prevBtn"),
            nextBtn: document.getElementById("nextBtn"),
            pageInfo: document.getElementById("pageInfo"),
            loadChartsBtn: document.getElementById("loadChartsBtn"),
            chartsWrap: document.getElementById("chartsWrap"),
            matieresChart: document.getElementById("matieresChart"),
            anneesChart: document.getElementById("anneesChart"),
            detailedQueryTotal: document.getElementById("detailedQueryTotal"),
            detailedTotalBooks: document.getElementById("detailedTotalBooks"),
            detailedTopMatiere: document.getElementById("detailedTopMatiere"),
            detailedYearRange: document.getElementById("detailedYearRange"),
            detailsModal: document.getElementById("detailsModal"),
            closeModalBtn: document.getElementById("closeModalBtn"),
            modalTitre: document.getElementById("modalTitre"),
            modalAuteur: document.getElementById("modalAuteur"),
            modalCote: document.getElementById("modalCote"),
            modalInventaire: document.getElementById("modalInventaire"),
            modalMatiere: document.getElementById("modalMatiere"),
            modalLangue: document.getElementById("modalLangue"),
            modalAnnee: document.getElementById("modalAnnee"),
            modalPages: document.getElementById("modalPages"),
            modalEdition: document.getElementById("modalEdition"),
            modalLieu: document.getElementById("modalLieu")
        };

        function debounce(callback, delay) {
            let timer = null;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => callback(...args), delay);
            };
        }

        const debouncedRunSearch = debounce(() => {
            state.page = 1;
            fetchResults();
        }, 300);

        function isArabic(text) {
            return /[\u0600-\u06FF]/.test(String(text || ""));
        }

        function escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/\"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function toDisplayNumber(value) {
            const number = Number(value || 0);
            return number.toLocaleString("fr-FR");
        }

        function getLanguageLabel(langue) {
            const key = String(langue || "").toLowerCase();
            if (key.startsWith("fr")) return "French";
            if (key.startsWith("en")) return "English";
            if (key.startsWith("ar")) return "Arabic";
            return "Unknown";
        }

        function pickFirst(...values) {
            for (const value of values) {
                if (value !== null && value !== undefined && String(value).trim() !== "") {
                    return value;
                }
            }
            return "";
        }

        function applyDirectionalText(element, value) {
            const text = String(value || "-");
            element.textContent = text;
            if (isArabic(text)) {
                element.classList.add("rtl-text");
                element.setAttribute("dir", "rtl");
            } else {
                element.classList.remove("rtl-text");
                element.setAttribute("dir", "ltr");
            }
        }

        function buildQuery(includePage = true) {
            const params = new URLSearchParams();
            if (state.q) params.set("q", state.q);
            if (state.matiere) params.set("matiere", state.matiere);
            if (state.langue) params.set("langue", state.langue);
            if (state.annee_min !== "") params.set("annee_min", state.annee_min);
            if (state.annee_max !== "") params.set("annee_max", state.annee_max);
            params.set("sort", state.sort);
            params.set("order", state.order);

            if (includePage) {
                params.set("page", String(state.page));
                params.set("per_page", String(state.per_page));
            }

            return params;
        }

        function setLoading(isLoading) {
            if (isLoading) {
                renderSkeleton(8);
                refs.skeletonGrid.classList.remove("hidden");
                refs.resultsGrid.classList.add("hidden");
                refs.emptyState.classList.add("hidden");
            } else {
                refs.skeletonGrid.classList.add("hidden");
                refs.resultsGrid.classList.remove("hidden");
            }
        }

        function renderSkeleton(count) {
            let html = "";
            for (let i = 0; i < count; i += 1) {
                html += `
                    <article class="skeleton-card">
                        <div class="skeleton-line short"></div>
                        <div class="skeleton-line long"></div>
                        <div class="skeleton-line medium"></div>
                        <div class="skeleton-line long"></div>
                    </article>
                `;
            }
            refs.skeletonGrid.innerHTML = html;
        }

        function renderEmpty(title = "Aucun resultat", message = "Essayez une autre combinaison de filtres.") {
            refs.resultsGrid.innerHTML = "";
            refs.emptyState.querySelector("h3").textContent = title;
            refs.emptyState.querySelector("p").textContent = message;
            refs.emptyState.classList.remove("hidden");
        }

        function renderCards(results) {
            if (!Array.isArray(results) || results.length === 0) {
                currentResults = [];
                renderEmpty();
                return;
            }

            refs.emptyState.classList.add("hidden");

            currentResults = results.map((book) => {
                return {
                    id: pickFirst(book.id, book.notice_id),
                    titre: pickFirst(book.titre, book.tit1, "Sans titre"),
                    auteur: pickFirst(book.auteur, book.author_name, "Auteur inconnu"),
                    cote: pickFirst(book.cote, book.expl_cote, "N/A"),
                    inventaire: pickFirst(book.inventaire, book.expl_cb, "N/A"),
                    matiere: pickFirst(book.matiere, book.libelle_categorie, "Non specifie"),
                    annee: pickFirst(book.annee, book.year, "N/A"),
                    langue: pickFirst(book.langue, book.code_langue, "N/A"),
                    lieu: pickFirst(book.lieu, book.ed_ville, "N/A"),
                    edition: pickFirst(book.edition, book.ed_name, "N/A"),
                    nb_pages: pickFirst(book.nb_pages, book.npages, book.pages, "N/A")
                };
            });

            const html = currentResults.map((book, index) => {
                const titre = book.titre;
                const auteur = book.auteur;
                const cote = book.cote;
                const inventaire = book.inventaire;
                const matiere = book.matiere;
                const annee = book.annee;
                const langue = book.langue;
                const lieu = book.lieu;
                const nbPages = book.nb_pages;
                const titleRtl = isArabic(titre) ? "rtl-text" : "";
                const authorRtl = isArabic(auteur) ? "rtl-text" : "";
                const matiereRtl = isArabic(matiere) ? "rtl-text" : "";
                const langLabel = getLanguageLabel(langue);
                const inventaireShort = String(inventaire).length > 14 ? `${String(inventaire).slice(0, 14)}...` : inventaire;

                return `
                    <article class="book-card">
                        <div class="book-top">
                            <h3 class="book-title ${titleRtl}" dir="${titleRtl ? "rtl" : "ltr"}">${escapeHtml(titre)}</h3>
                            <span class="inventory-badge" title="${escapeHtml(inventaire)}">${escapeHtml(inventaireShort)}</span>
                        </div>
                        <p class="book-author ${authorRtl}" dir="${authorRtl ? "rtl" : "ltr"}">
                            <i data-lucide="user"></i>
                            <span>${escapeHtml(auteur)}</span>
                        </p>
                        <div class="book-info-grid">
                            <span class="book-inline-item">
                                <i data-lucide="map-pin"></i>
                                <span>${escapeHtml(lieu)}</span>
                            </span>
                            <span class="book-inline-item">
                                <i data-lucide="calendar-days"></i>
                                <span>${escapeHtml(annee)}</span>
                            </span>
                            <span class="book-inline-item ${matiereRtl}" dir="${matiereRtl ? "rtl" : "ltr"}">
                                <i data-lucide="bookmark"></i>
                                <span>${escapeHtml(matiere)}</span>
                            </span>
                            <span class="book-inline-item">
                                <i data-lucide="file-text"></i>
                                <span>${escapeHtml(nbPages)} p.</span>
                            </span>
                        </div>
                        <div class="book-bottom">
                            <div class="book-footer-left">
                                <span class="cote-badge"><i data-lucide="barcode"></i> ${escapeHtml(cote)}</span>
                                <span class="lang-badge">${escapeHtml(langLabel)}</span>
                            </div>
                            <div class="book-actions">
                                <button type="button" class="details-btn" data-index="${index}"><i data-lucide="info"></i>Détails</button>
                            </div>
                        </div>
                    </article>
                `;
            }).join("");

            refs.resultsGrid.innerHTML = html;
            lucide.createIcons();
        }

        function openDetailsModal(index) {
            const book = currentResults[index];
            if (!book) return;

            applyDirectionalText(refs.modalTitre, book.titre || "-");
            applyDirectionalText(refs.modalAuteur, book.auteur || "-");
            applyDirectionalText(refs.modalCote, book.cote || "-");
            applyDirectionalText(refs.modalInventaire, book.inventaire || "-");
            applyDirectionalText(refs.modalMatiere, book.matiere || "-");

            const langue = getLanguageLabel(book.langue);
            applyDirectionalText(refs.modalLangue, langue);
            applyDirectionalText(refs.modalAnnee, book.annee || "-");
            applyDirectionalText(refs.modalPages, book.nb_pages || "-");
            applyDirectionalText(refs.modalEdition, book.edition || "-");
            applyDirectionalText(refs.modalLieu, book.lieu || "-");

            refs.detailsModal.classList.remove("hidden");
            refs.detailsModal.setAttribute("aria-hidden", "false");
            document.body.style.overflow = "hidden";
        }

        function closeDetailsModal() {
            refs.detailsModal.classList.add("hidden");
            refs.detailsModal.setAttribute("aria-hidden", "true");
            document.body.style.overflow = "";
        }

        function updatePagination(total, page, totalPages) {
            const safeTotal = Number(total || 0);
            state.page = Math.max(Number(page || 1), 1);
            state.total_pages = Math.max(Number(totalPages || 1), 1);

            refs.queryTotalChip.textContent = toDisplayNumber(safeTotal);
            refs.detailedQueryTotal.textContent = toDisplayNumber(safeTotal);
            refs.pageInfo.textContent = `Page ${state.page} sur ${state.total_pages}`;
            refs.prevBtn.disabled = state.page <= 1;
            refs.nextBtn.disabled = state.page >= state.total_pages;

            const activeFilters = [];
            if (state.q) activeFilters.push(`q: "${state.q}"`);
            if (state.matiere) activeFilters.push(`matiere: ${state.matiere}`);
            if (state.langue) activeFilters.push(`langue: ${getLanguageLabel(state.langue)}`);
            if (state.annee_min) activeFilters.push(`annee >= ${state.annee_min}`);
            if (state.annee_max) activeFilters.push(`annee <= ${state.annee_max}`);

            refs.resultsMeta.textContent = activeFilters.length > 0
                ? `${toDisplayNumber(safeTotal)} resultat(s) | ${activeFilters.join(" | ")}`
                : `${toDisplayNumber(safeTotal)} resultat(s) | Tous les livres`;
        }

        function updateDetailedStaticCounters(totalBooks, topMatiere, yearRange) {
            refs.detailedTotalBooks.textContent = totalBooks;
            refs.detailedTopMatiere.textContent = topMatiere;
            refs.detailedYearRange.textContent = yearRange;
        }

        function chartTheme() {
            const isLight = document.body.classList.contains("theme-light");
            return {
                textColor: isLight ? "#42567f" : "#c9d7f5",
                gridColor: isLight ? "rgba(57,88,142,0.15)" : "rgba(146,165,202,0.22)",
                barColor: [
                    "rgba(79,124,255,0.78)",
                    "rgba(127,91,255,0.72)",
                    "rgba(54,162,235,0.72)",
                    "rgba(255,159,64,0.72)",
                    "rgba(75,192,192,0.72)",
                    "rgba(255,99,132,0.72)"
                ],
                lineColor: isLight ? "rgba(64,104,199,0.95)" : "rgba(95,143,255,0.95)",
                lineFill: isLight ? "rgba(79,124,255,0.18)" : "rgba(79,124,255,0.24)"
            };
        }

        function destroyCharts() {
            if (chartInstances.matieres) {
                chartInstances.matieres.destroy();
                chartInstances.matieres = null;
            }
            if (chartInstances.annees) {
                chartInstances.annees.destroy();
                chartInstances.annees = null;
            }
        }

        function renderMatieresBarChart(matieres) {
            if (!refs.matieresChart) return;

            const source = (Array.isArray(matieres) ? matieres : []).slice(0, 10);
            if (source.length === 0) {
                if (chartInstances.matieres) {
                    chartInstances.matieres.destroy();
                    chartInstances.matieres = null;
                }
                return;
            }

            const ctx = refs.matieresChart.getContext("2d");
            if (chartInstances.matieres) {
                chartInstances.matieres.destroy();
            }

            const theme = chartTheme();

            chartInstances.matieres = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: source.map((item) => item?.matiere || "Non spécifié"),
                    datasets: [{
                        label: "Nombre de livres",
                        data: source.map((item) => Number(item?.count || 0)),
                        backgroundColor: source.map((_, idx) => theme.barColor[idx % theme.barColor.length]),
                        borderWidth: 0,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: theme.textColor } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: theme.gridColor },
                            ticks: { color: theme.textColor }
                        },
                        x: {
                            grid: { color: theme.gridColor },
                            ticks: { color: theme.textColor, maxRotation: 45, minRotation: 20 }
                        }
                    }
                }
            });
        }

        function renderAnneesLineChart(annees) {
            if (!refs.anneesChart) return;

            const source = (Array.isArray(annees) ? annees : [])
                .map((item) => ({ annee: Number(item?.annee), count: Number(item?.count || 0) }))
                .filter((item) => Number.isFinite(item.annee) && item.annee > 0)
                .sort((a, b) => a.annee - b.annee)
                .slice(-12);

            if (source.length === 0) {
                if (chartInstances.annees) {
                    chartInstances.annees.destroy();
                    chartInstances.annees = null;
                }
                return;
            }

            const ctx = refs.anneesChart.getContext("2d");
            if (chartInstances.annees) {
                chartInstances.annees.destroy();
            }

            const theme = chartTheme();

            chartInstances.annees = new Chart(ctx, {
                type: "line",
                data: {
                    labels: source.map((item) => item.annee),
                    datasets: [{
                        label: "Nombre de livres",
                        data: source.map((item) => item.count),
                        borderColor: theme.lineColor,
                        backgroundColor: theme.lineFill,
                        fill: true,
                        tension: 0.25,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: theme.textColor } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: theme.gridColor },
                            ticks: { color: theme.textColor }
                        },
                        x: {
                            grid: { color: theme.gridColor },
                            ticks: { color: theme.textColor }
                        }
                    }
                }
            });
        }

        function renderDetailedCharts() {
            renderMatieresBarChart(statsCache?.matieres || []);
            renderAnneesLineChart(statsCache?.annees || []);
        }

        function toggleDetailedCharts() {
            const isHidden = refs.chartsWrap.classList.contains("hidden");

            if (isHidden) {
                renderDetailedCharts();
                refs.chartsWrap.classList.remove("hidden");
                refs.loadChartsBtn.textContent = 'Masquer les graphiques';
            } else {
                refs.chartsWrap.classList.add("hidden");
                refs.loadChartsBtn.textContent = 'Charger les graphiques détaillés';
                destroyCharts();
            }
        }

        async function fetchResults() {
            if (!refs.detailsModal.classList.contains("hidden")) {
                closeDetailsModal();
            }

            if (activeController) {
                activeController.abort();
            }

            const controller = new AbortController();
            activeController = controller;
            setLoading(true);

            try {
                const query = buildQuery(true).toString();
                const response = await fetch(`search.php?${query}`, {
                    signal: controller.signal,
                    headers: { "Accept": "application/json" }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                if (payload.error) {
                    throw new Error(payload.error);
                }

                renderCards(payload.results || []);
                updatePagination(payload.total || 0, payload.page || 1, payload.total_pages || 1);
            } catch (error) {
                if (error.name === "AbortError") {
                    return;
                }
                renderEmpty("Erreur de chargement", error.message || "Impossible de recuperer les resultats.");
                updatePagination(0, 1, 1);
            } finally {
                if (activeController === controller) {
                    setLoading(false);
                    activeController = null;
                }
            }
        }

        function populateMatiereOptions(matieres) {
            const initial = '<option value="">Toutes les matieres</option>';
            if (!Array.isArray(matieres) || matieres.length === 0) {
                refs.matiereSelect.innerHTML = initial;
                return;
            }

            const options = matieres.map((item) => {
                const label = item && item.matiere ? item.matiere : "Non specifie";
                const count = item && item.count ? ` (${item.count})` : "";
                return `<option value="${escapeHtml(label)}">${escapeHtml(label + count)}</option>`;
            }).join("");

            refs.matiereSelect.innerHTML = initial + options;
        }

        function computeYearRange(stats) {
            if (stats && stats.annee_range && Number(stats.annee_range.min) && Number(stats.annee_range.max)) {
                return {
                    min: Number(stats.annee_range.min),
                    max: Number(stats.annee_range.max)
                };
            }

            if (Array.isArray(stats?.annees) && stats.annees.length > 0) {
                const years = stats.annees
                    .map((row) => Number(row.annee))
                    .filter((year) => Number.isFinite(year) && year > 0);

                if (years.length > 0) {
                    return {
                        min: Math.min(...years),
                        max: Math.max(...years)
                    };
                }
            }

            return null;
        }

        async function fetchStats() {
            try {
                const response = await fetch("stats.php", {
                    headers: { "Accept": "application/json" }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                if (payload.error) {
                    throw new Error(payload.error);
                }

                statsCache = payload;

                const total = Number(payload.total || 0);
                refs.dbTotalCount.textContent = `${toDisplayNumber(total)} livres`;
                refs.totalBooksChip.textContent = toDisplayNumber(total);

                const topMatiere = Array.isArray(payload.matieres) && payload.matieres[0]
                    ? payload.matieres[0].matiere
                    : "--";
                refs.topMatiereChip.textContent = topMatiere || "--";

                let yearRangeLabel = "--";

                const yearRange = computeYearRange(payload);
                if (yearRange) {
                    yearRangeLabel = `${yearRange.min} - ${yearRange.max}`;
                    refs.yearRangeChip.textContent = yearRangeLabel;
                    refs.yearHint.textContent = `Plage: ${yearRange.min} - ${yearRange.max}`;
                    refs.yearMin.min = String(yearRange.min);
                    refs.yearMin.max = String(yearRange.max);
                    refs.yearMax.min = String(yearRange.min);
                    refs.yearMax.max = String(yearRange.max);
                    refs.yearMin.placeholder = String(yearRange.min);
                    refs.yearMax.placeholder = String(yearRange.max);
                } else {
                    refs.yearRangeChip.textContent = "--";
                    refs.yearHint.textContent = "Plage: --";
                }

                populateMatiereOptions(payload.matieres || []);
                updateDetailedStaticCounters(
                    refs.totalBooksChip.textContent,
                    refs.topMatiereChip.textContent,
                    yearRangeLabel
                );

                if (!refs.chartsWrap.classList.contains("hidden")) {
                    renderDetailedCharts();
                }
            } catch (error) {
                refs.dbTotalCount.textContent = "Stats indisponibles";
                refs.totalBooksChip.textContent = "--";
                refs.topMatiereChip.textContent = "--";
                refs.yearRangeChip.textContent = "--";
                refs.yearHint.textContent = "Plage: --";
                updateDetailedStaticCounters("--", "--", "--");
            }
        }

        function isMobileView() {
            return window.innerWidth <= 980;
        }

        function closeSidebar() {
            if (isMobileView()) {
                refs.sidebar.classList.remove("open");
                refs.sidebarOverlay.classList.remove("visible");
                return;
            }

            refs.layout.classList.add("sidebar-collapsed");
        }

        function openSidebar() {
            if (isMobileView()) {
                refs.sidebar.classList.add("open");
                refs.sidebarOverlay.classList.add("visible");
                return;
            }

            refs.layout.classList.remove("sidebar-collapsed");
        }

        function syncSidebarOnResize() {
            if (isMobileView()) {
                refs.layout.classList.remove("sidebar-collapsed");
            } else {
                refs.sidebar.classList.remove("open");
                refs.sidebarOverlay.classList.remove("visible");
            }
        }

        function applyOrderUi() {
            refs.orderLabel.textContent = state.order;
            refs.orderArrow.textContent = state.order === "ASC" ? "↑" : "↓";
        }

        function updateThemeToggleIcon() {
            const isLight = document.body.classList.contains("theme-light");
            refs.themeToggleBtn.innerHTML = isLight
                ? '<i data-lucide="moon"></i>'
                : '<i data-lucide="sun"></i>';
            lucide.createIcons();
        }

        function applySavedTheme() {
            const savedTheme = localStorage.getItem("library-theme");
            if (savedTheme === "dark") {
                document.body.classList.remove("theme-light");
            } else {
                document.body.classList.add("theme-light");
            }
            updateThemeToggleIcon();
        }

        function toggleTheme() {
            document.body.classList.toggle("theme-light");
            const isLight = document.body.classList.contains("theme-light");
            localStorage.setItem("library-theme", isLight ? "light" : "dark");
            updateThemeToggleIcon();

            if (!refs.chartsWrap.classList.contains("hidden")) {
                renderDetailedCharts();
            }
        }

        function setActiveLanguage(lang) {
            const buttons = refs.languageButtons.querySelectorAll(".lang-btn");
            buttons.forEach((btn) => {
                btn.classList.toggle("active", btn.dataset.lang === lang);
            });
        }

        function resetFilters() {
            state.q = "";
            state.langue = "";
            state.matiere = "";
            state.annee_min = "";
            state.annee_max = "";
            state.sort = "titre";
            state.order = "ASC";
            state.page = 1;

            refs.searchInput.value = "";
            refs.matiereSelect.value = "";
            refs.yearMin.value = "";
            refs.yearMax.value = "";
            refs.sortField.value = "titre";
            applyOrderUi();
            setActiveLanguage("");

            fetchResults();
        }

        function bindEvents() {
            refs.openSidebarBtn.addEventListener("click", openSidebar);
            refs.closeSidebarBtn.addEventListener("click", closeSidebar);
            refs.sidebarOverlay.addEventListener("click", closeSidebar);
            refs.themeToggleBtn.addEventListener("click", toggleTheme);
            window.addEventListener("resize", syncSidebarOnResize);

            refs.searchInput.addEventListener("input", (event) => {
                state.q = event.target.value.trim();
                debouncedRunSearch();
            });

            refs.languageButtons.addEventListener("click", (event) => {
                const target = event.target.closest(".lang-btn");
                if (!target) return;
                state.langue = target.dataset.lang || "";
                state.page = 1;
                setActiveLanguage(state.langue);
                fetchResults();
                if (window.innerWidth <= 980) closeSidebar();
            });

            refs.matiereSelect.addEventListener("change", (event) => {
                state.matiere = event.target.value;
                state.page = 1;
                fetchResults();
            });

            refs.yearMin.addEventListener("input", (event) => {
                state.annee_min = event.target.value.trim();
                debouncedRunSearch();
            });

            refs.yearMax.addEventListener("input", (event) => {
                state.annee_max = event.target.value.trim();
                debouncedRunSearch();
            });

            refs.sortField.addEventListener("change", (event) => {
                state.sort = event.target.value;
                state.page = 1;
                fetchResults();
            });

            refs.orderToggle.addEventListener("click", () => {
                state.order = state.order === "ASC" ? "DESC" : "ASC";
                applyOrderUi();
                state.page = 1;
                fetchResults();
            });

            refs.loadChartsBtn.addEventListener("click", async () => {
                if (!statsCache) {
                    await fetchStats();
                }
                toggleDetailedCharts();
            });

            refs.resetBtn.addEventListener("click", resetFilters);

            refs.prevBtn.addEventListener("click", () => {
                if (state.page <= 1) return;
                state.page -= 1;
                fetchResults();
            });

            refs.nextBtn.addEventListener("click", () => {
                if (state.page >= state.total_pages) return;
                state.page += 1;
                fetchResults();
            });

            refs.resultsGrid.addEventListener("click", (event) => {
                const button = event.target.closest(".details-btn");
                if (!button) return;
                const index = Number(button.dataset.index);
                if (Number.isNaN(index)) return;
                openDetailsModal(index);
            });

            refs.closeModalBtn.addEventListener("click", closeDetailsModal);

            refs.detailsModal.addEventListener("click", (event) => {
                if (event.target === refs.detailsModal) {
                    closeDetailsModal();
                }
            });

            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape" && !refs.detailsModal.classList.contains("hidden")) {
                    closeDetailsModal();
                }
            });

            refs.exportBtn.addEventListener("click", () => {
                const query = buildQuery(false).toString();
                const target = query ? `export.php?${query}` : "export.php";
                window.location.href = target;
            });
        }

        async function init() {
            lucide.createIcons();
            applySavedTheme();
            syncSidebarOnResize();
            applyOrderUi();
            bindEvents();
            renderSkeleton(8);
            await fetchStats();
            await fetchResults();
        }

        init();
    </script>
</body>
</html>
