<?php
// This file is included by index.php, so DB and auth are checked.

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

try {
    // Handle POST requests (Create, Update, Delete)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add'])) {
            $name = $_POST['name'] ?? '';
            $languages = $_POST['languages'] ?? '';
            if (!empty($name)) {
                $stmt = $pdo->prepare("INSERT INTO softwares (name, languages) VALUES (?, ?)");
                $stmt->execute([$name, $languages]);
                $success = "Software added successfully.";
            } else {
                $error = "Software name is required.";
            }
        } elseif (isset($_POST['update'])) {
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';
            $languages = $_POST['languages'] ?? '';
            if (!empty($id) && !empty($name)) {
                $stmt = $pdo->prepare("UPDATE softwares SET name = ?, languages = ? WHERE id = ?");
                $stmt->execute([$name, $languages, $id]);
                $success = "Software updated successfully.";
            } else {
                $error = "ID and Name are required for update.";
            }
        }
    }

    // Handle DELETE request (via GET parameter for simplicity)
    if ($action === 'delete' && $id) {
        try {
            // Check for related versions first
             $stmt = $pdo->prepare("SELECT COUNT(*) FROM versions WHERE software_id = ?");
             $stmt->execute([$id]);
             if ($stmt->fetchColumn() > 0) {
                 $error = "Cannot delete software. It has related versions. Delete the versions first.";
             } else {
                $stmt = $pdo->prepare("DELETE FROM softwares WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Software deleted successfully.";
             }
        } catch (PDOException $e) {
             // Catch foreign key constraint errors
             $error = "Could not delete software. Check for related versions or licences.";
        }
    }

    // Fetch data for 'edit' form
    $software_to_edit = null;
    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare("SELECT * FROM softwares WHERE id = ?");
        $stmt->execute([$id]);
        $software_to_edit = $stmt->fetch();
    }

    // Fetch all softwares for the list
    $softwares = $pdo->query("SELECT * FROM softwares ORDER BY name")->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-4" role="alert">
        <p><?php echo e($error); ?></p>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md mb-4" role="alert">
        <p><?php echo e($success); ?></p>
    </div>
<?php endif; ?>

<div class="mb-6 bg-gray-50 p-4 rounded-lg shadow-sm">
    <h2 class="text-lg font-medium text-gray-900 mb-4">
        <?php echo $action === 'edit' ? 'Edit Software' : 'Add New Software'; ?>
    </h2>
    <form action="index.php?page=softwares" method="POST">
        <?php if ($action === 'edit' && $software_to_edit): ?>
            <input type="hidden" name="id" value="<?php echo e($software_to_edit['id']); ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Software Name</label>
                <input type="text" name="name" id="name" required
                       value="<?php echo e($software_to_edit['name'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="languages" class="block text-sm font-medium text-gray-700">Languages (comma-separated)</label>
                <input type="text" name="languages" id="languages"
                       value="<?php echo e($software_to_edit['languages'] ?? ''); ?>"
                       placeholder="e.g., php,js,java"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex items-end">
                <?php if ($action === 'edit'): ?>
                    <button type="submit" name="update" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Software
                    </button>
                    <a href="index.php?page=softwares" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                <?php else: ?>
                    <button type="submit" name="add" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Add Software
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div x-data="{ search: '' }">
    <h2 class="text-lg font-medium text-gray-900 mb-4">Existing Softwares</h2>

    <div class="mb-4">
        <label for="searchSoftwares" class="sr-only">Search Softwares</label>
        <input type="search" name="search" id="searchSoftwares" x-model.debounce.300ms="search" 
               placeholder="Search softwares by name or language..."
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
    </div>

    <div class="overflow-x-auto shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Languages</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($softwares)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No softwares found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($softwares as $software): ?>
                    <?php 
                        $searchable_data = strtolower($software['name'] . ' ' . $software['languages']);
                    ?>
                    <tr x-show="search.trim() === '' || '<?php echo e($searchable_data); ?>'.includes(search.toLowerCase())">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($software['id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($software['name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php foreach (explode(',', $software['languages']) as $lang): ?>
                                <?php if(trim($lang)): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php echo e(trim($lang)); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="index.php?page=softwares&action=edit&id=<?php echo e($software['id']); ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <a href="index.php?page=softwares&action=delete&id=<?php echo e($software['id']); ?>" 
                               class="text-red-600 hover:text-red-900 ml-4"
                               onclick="return confirm('Are you sure you want to delete this software? This action cannot be undone.');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>