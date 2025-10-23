<?php
// This file is included by index.php, so config.php is already loaded.
$current_page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Server Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Note: This uses default Tailwind values for colors, spacing, and fonts.
        rgb(209 213 219) = text-gray-300
        rgb(55 65 81)   = bg-gray-700
        rgb(17 24 39)    = bg-gray-900
        rgb(255 255 255) = text-white
        */

        .nav-link {
            display: block;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            border-radius: 0.375rem;
            font-size: 1rem;
            line-height: 1.5rem;
            font-weight: 500;
            color: rgb(209 213 219);
        }

        .nav-link:hover {
            background-color: rgb(55 65 81);
            color: rgb(255 255 255);
        }

        .nav-link-active {
            background-color: rgb(17 24 39);
            color: rgb(255 255 255);
        }

        .nav-link-desktop {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
            color: rgb(209 213 219);
        }

        .nav-link-desktop:hover {
            background-color: rgb(55 65 81);
            color: rgb(255 255 255);
        }

        .nav-link-desktop-active {
            background-color: rgb(17 24 39);
            color: rgb(255 255 255);
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
</head>
<body class="h-full">
<div class="min-h-full">
    <nav class="bg-gray-800" x-data="{ open: false }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="index.php?page=dashboard" class="nav-link-desktop <?php echo $current_page == 'dashboard' ? 'nav-link-desktop-active' : ''; ?>">Dashboard</a>
                            <a href="index.php?page=softwares" class="nav-link-desktop <?php echo $current_page == 'softwares' ? 'nav-link-desktop-active' : ''; ?>">Softwares</a>
                            <a href="index.php?page=versions" class="nav-link-desktop <?php echo $current_page == 'versions' ? 'nav-link-desktop-active' : ''; ?>">Versions</a>
                            <a href="index.php?page=customers" class="nav-link-desktop <?php echo $current_page == 'customers' ? 'nav-link-desktop-active' : ''; ?>">Customers</a>
                            <a href="index.php?page=licences" class="nav-link-desktop <?php echo $current_page == 'licences' ? 'nav-link-desktop-active' : ''; ?>">Licences</a>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <span class="text-gray-400 mr-3 text-sm">Welcome, <?php echo e($_SESSION['username']); ?>!</span>
                        <a href="logout.php" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Log out
                        </a>
                    </div>
                </div>
                <div class="-mr-2 flex md:hidden">
                    <!-- Mobile menu button -->
                    <button @click="open = !open" type="button" class="inline-flex items-center justify-center rounded-md bg-gray-800 p-2 text-gray-400 hover:bg-gray-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg :class="{'hidden': open, 'block': !open }" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <svg :class="{'block': open, 'hidden': !open }" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu, show/hide based on menu state. -->
        <div class="md:hidden" id="mobile-menu" x-show="open" @click.away="open = false">
            <div class="space-y-1 px-2 pb-3 pt-2 sm:px-3">
                <a href="index.php?page=dashboard" class="nav-link <?php echo $current_page == 'dashboard' ? 'nav-link-active' : ''; ?>">Dashboard</a>
                <a href="index.php?page=softwares" class="nav-link <?php echo $current_page == 'softwares' ? 'nav-link-active' : ''; ?>">Softwares</a>
                <a href="index.php?page=versions" class="nav-link <?php echo $current_page == 'versions' ? 'nav-link-active' : ''; ?>">Versions</a>
                <a href="index.php?page=customers" class="nav-link <?php echo $current_page == 'customers' ? 'nav-link-active' : ''; ?>">Customers</a>
                <a href="index.php?page=licences" class="nav-link <?php echo $current_page == 'licences' ? 'nav-link-active' : ''; ?>">Licences</a>
            </div>
            <div class="border-t border-gray-700 pb-3 pt-4">
                <div class="flex items-center px-5">
                    <div class="flex-shrink-0">
                        <!-- User avatar placeholder -->
                        <span class="inline-block h-10 w-10 overflow-hidden rounded-full bg-gray-600">
                          <svg class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                          </svg>
                        </span>
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium leading-none text-white"><?php echo e($_SESSION['username']); ?></div>
                    </div>
                </div>
                <div class="mt-3 space-y-1 px-2">
                    <a href="logout.php" class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">Log out</a>
                </div>
            </div>
        </div>
    </nav>

    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <h1 class="text-xl font-semibold leading-tight tracking-tight text-gray-900">
                <?php echo e(ucfirst($current_page)); ?>
            </h1>
        </div>
    </header>
    <main>
        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
            <!-- Your content -->
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white rounded-lg shadow-md p-6">
