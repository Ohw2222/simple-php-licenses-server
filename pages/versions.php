<?php
// This file is included by index.php, so DB and auth are checked.

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

try {
    // Handle POST requests (Create, Update)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add'])) {
            $software_id = $_POST['software_id'] ?? '';
            $version_number = $_POST['version_number'] ?? '';
            if (!empty($software_id) && !empty($version_number)) {
                $stmt = $pdo->prepare("INSERT INTO versions (software_id, version_number) VALUES (?, ?)");
                $stmt->execute([$software_id, $version_number]);
                $success = "Version added successfully.";
            } else {
                $error = "Software and Version Number are required.";
            }
        } elseif (isset($_POST['update'])) {
            $id = $_POST['id'] ?? null;
            $software_id = $_POST['software_id'] ?? '';
            $version_number = $_POST['version_number'] ?? '';
            if (!empty($id) && !empty($software_id) && !empty($version_number)) {
                $stmt = $pdo->prepare("UPDATE versions SET software_id = ?, version_number = ? WHERE id = ?");
                $stmt->execute([$software_id, $version_number, $id]);
                $success = "Version updated successfully.";
            } else {
                $error = "ID, Software, and Version Number are required for update.";
            }
        }
    }

    // Handle DELETE request
    if ($action === 'delete' && $id) {
         try {
             // Check for related licences first
             $stmt = $pdo->prepare("SELECT COUNT(*) FROM licences WHERE version_id = ?");
             $stmt->execute([$id]);
             if ($stmt->fetchColumn() > 0) {
                 $error = "Cannot delete version. It is used in existing licences. Delete the licences first.";
             } else {
                $stmt = $pdo->prepare("DELETE FROM versions WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Version deleted successfully.";
             }
         } catch (PDOException $e) {
             $error = "Could not delete version. Check for related licences.";
         }
    }

    // Fetch data for 'edit' form
    $version_to_edit = null;
    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare("SELECT * FROM versions WHERE id = ?");
        $stmt->execute([$id]);
        $version_to_edit = $stmt->fetch();
    }

    // Fetch all softwares for the dropdown
    $softwares = $pdo->query("SELECT id, name FROM softwares ORDER BY name")->fetchAll();
    
    // Fetch all versions with software names
    $versions = $pdo->query("
        SELECT v.id, v.version_number, s.name as software_name
        FROM versions v
        JOIN softwares s ON v.software_id = s.id
        ORDER BY s.name, v.version_number DESC
    ")->fetchAll();

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
        <?php echo $action === 'edit' ? 'Edit Version' : 'Add New Version'; ?>
    </h2>
    <form action="index.php?page=versions" method="POST">
        <?php if ($action === 'edit' && $version_to_edit): ?>
            <input type="hidden" name="id" value="<?php echo e($version_to_edit['id']); ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="software_id" class="block text-sm font-medium text-gray-700">Software</label>
                <select name="software_id" id="software_id" required
                        class="mt-1 block w-full">
                    <option value="">-- Select Software --</option>
                    <?php foreach ($softwares as $software): ?>
                        <option value="<?php echo e($software['id']); ?>" 
                            <?php echo (isset($version_to_edit['software_id']) && $version_to_edit['software_id'] == $software['id']) ? 'selected' : ''; ?>>
                            <?php echo e($software['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="version_number" class="block text-sm font-medium text-gray-700">Version Number</label>
                <input type="text" name="version_number" id="version_number" required
                       value="<?php echo e($version_to_edit['version_number'] ?? ''); ?>"
                       placeholder="e.g., 1.0.0"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex items-end">
                <?php if ($action === 'edit'): ?>
                    <button type="submit" name="update" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Version
                    </button>
                    <a href="index.php?page=versions" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                <?php else: ?>
                    <button type="submit" name="add" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Add Version
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div x-data="{ search: '' }">
    <h2 class="text-lg font-medium text-gray-900 mb-4">Existing Versions</h2>

    <div class="mb-4">
        <label for="searchVersions" class="sr-only">Search Versions</label>
        <input type="search" name="search" id="searchVersions" x-model.debounce.300ms="search" 
               placeholder="Search versions by software name or version number..."
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
    </div>

    <div class="overflow-x-auto shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Software</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version Number</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($versions)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No versions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($versions as $version): ?>
                    <?php 
                        $searchable_data = strtolower($version['software_name'] . ' ' . $version['version_number']);
                    ?>
                    <tr x-show="search.trim() === '' || '<?php echo e($searchable_data); ?>'.includes(search.toLowerCase())">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($version['id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($version['software_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($version['version_number']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="index.php?page=versions&action=edit&id=<?php echo e($version['id']); ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <a href="index.php?page=versions&action=delete&id=<?php echo e($version['id']); ?>" 
                               class="text-red-600 hover:text-red-900 ml-4"
                               onclick="return confirm('Are you sure you want to delete this version?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Initialize Tom Select for the software dropdown
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('software_id')) {
            new TomSelect('#software_id', {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        }
    });
</script>