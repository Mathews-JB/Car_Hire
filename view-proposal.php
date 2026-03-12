<?php
// view-proposal.php - Professional Academic Proposal Viewer
$proposal_file = 'PROJECT_PROPOSAL_CAR_HIRE.md';
$content = file_get_contents($proposal_file);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Hire: Project Proposal - Department of ICT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.2.0/github-markdown.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700;900&family=Roboto+Mono:wght@400&display=swap" rel="stylesheet">
    <!-- Theme System -->
    <link rel="stylesheet" href="public/css/theme.css?v=4.0">
    <script src="public/js/theme-switcher.js?v=4.0"></script>
    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2c5282;
            --accent-color: #3b82f6;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-color: #e2e8f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.8;
            color: var(--text-dark);
            counter-reset: page;
        }
        
        /* Header Bar */
        .header-bar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5em;
            font-weight: 900;
        }
        
        .header-subtitle {
            font-size: 0.85em;
            opacity: 0.9;
            margin-top: 4px;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: white;
            color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Main Container */
        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 80px 100px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border-radius: 12px;
            position: relative;
        }
        
        /* Cover Page Styling */
        .cover-page {
            text-align: center;
            padding: 100px 0;
            border-bottom: 3px solid var(--primary-color);
            margin-bottom: 60px;
        }
        
        .dept-name {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--primary-color);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 40px;
        }
        
        .proposal-title {
            font-family: 'Playfair Display', serif;
            font-size: 3em;
            font-weight: 900;
            color: var(--primary-color);
            line-height: 1.2;
            margin-bottom: 20px;
        }
        
        .proposal-subtitle {
            font-size: 1.3em;
            color: var(--text-light);
            margin-bottom: 60px;
        }
        
        /* Content Styling */
        .markdown-body {
            font-size: 1.05em;
            line-height: 1.9;
        }
        
        .markdown-body h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5em;
            color: var(--primary-color);
            border-bottom: 3px solid var(--accent-color);
            padding-bottom: 15px;
            margin-top: 60px;
            margin-bottom: 30px;
            page-break-after: avoid;
        }
        
        .markdown-body h2 {
            font-size: 1.8em;
            color: var(--secondary-color);
            margin-top: 40px;
            margin-bottom: 20px;
            border-left: 4px solid var(--accent-color);
            padding-left: 20px;
            page-break-after: avoid;
        }
        
        .markdown-body h3 {
            font-size: 1.4em;
            color: var(--text-dark);
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .markdown-body h4 {
            font-size: 1.1em;
            color: var(--text-dark);
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .markdown-body p {
            margin-bottom: 1.2em;
            text-align: justify;
        }
        
        .markdown-body ul, .markdown-body ol {
            margin-left: 30px;
            margin-bottom: 1.5em;
        }
        
        .markdown-body li {
            margin-bottom: 0.8em;
        }
        
        .markdown-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 0.95em;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .markdown-body thead tr {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: left;
            font-weight: 600;
        }
        
        .markdown-body th, .markdown-body td {
            padding: 15px 18px;
            border: 1px solid var(--border-color);
        }
        
        .markdown-body tbody tr {
            border-bottom: 1px solid var(--border-color);
        }
        
        .markdown-body tbody tr:nth-of-type(even) {
            background-color: #f8fafc;
        }
        
        .markdown-body tbody tr:hover {
            background-color: #e0f2fe;
        }
        
        .markdown-body code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Roboto Mono', monospace;
            font-size: 0.9em;
            color: #e11d48;
        }
        
        .markdown-body pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 20px 0;
        }
        
        .markdown-body pre code {
            background: transparent;
            color: inherit;
            padding: 0;
        }
        
        .markdown-body blockquote {
            border-left: 4px solid var(--accent-color);
            padding-left: 20px;
            margin: 20px 0;
            color: var(--text-light);
            font-style: italic;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white;
                counter-reset: page;
            }
            
            .header-bar, .action-buttons, .no-print {
                display: none !important;
            }
            
            .container {
                max-width: 100%;
                margin: 0;
                padding: 40px;
                box-shadow: none;
                border-radius: 0;
            }
            
            .markdown-body h1, .markdown-body h2 {
                page-break-after: avoid;
            }
            
            .markdown-body h1 {
                page-break-before: always;
            }
            
            .markdown-body h1:first-of-type {
                page-break-before: avoid;
            }
            
            .markdown-body table {
                page-break-inside: avoid;
            }
            
            .markdown-body pre {
                page-break-inside: avoid;
            }
            
            /* Page numbering */
            @page {
                margin: 2cm;
                @bottom-right {
                    content: "Page " counter(page);
                    font-size: 10pt;
                    color: #64748b;
                }
            }
            
            /* Avoid orphans and widows */
            p, li {
                orphans: 3;
                widows: 3;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 40px 30px;
                margin: 20px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .proposal-title {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="header-bar no-print">
        <div class="header-content">
            <div>
                <div class="header-title">Car Hire Project Proposal</div>
                <div class="header-subtitle">Department of Information and Communication Technology</div>
            </div>
            <div class="action-buttons">
                <a href="javascript:window.print()" class="btn btn-primary">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
                        <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    </svg>
                    Save as PDF
                </a>
                <a href="PROJECT_PROPOSAL_CAR_hire.md" download class="btn btn-secondary">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                    </svg>
                    Download .MD
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <article class="markdown-body" id="content">
            <!-- Content will be rendered here -->
        </article>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script>
        // Configure Mermaid
        mermaid.initialize({ 
            startOnLoad: true,
            theme: 'default',
            securityLevel: 'loose',
            flowchart: {
                useMaxWidth: true,
                htmlLabels: true
            }
        });
        
        // Configure Marked
        marked.setOptions({
            breaks: true,
            gfm: true,
            headerIds: true,
            mangle: false
        });
        
        // Parse and render markdown
        const markdown = <?php echo json_encode($content); ?>;
        document.getElementById('content').innerHTML = marked.parse(markdown);
        
        // Re-initialize Mermaid after content is loaded
        mermaid.init(undefined, document.querySelectorAll('.language-mermaid'));
        
        // Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
