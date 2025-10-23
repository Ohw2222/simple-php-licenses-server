<?php
// This file is included by index.php, so DB and auth are checked.

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

try {
    // Handle POST requests (Create, Update)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $licence_name = $_POST['licence_name'] ?? '';
        $software_id = $_POST['software_id'] ?? '';
        $version_id = $_POST['version_id'] ?? '';
        $customer_id = $_POST['customer_id'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $price = $_POST['price'] ?? 0.00;
        $message = $_POST['message'] ?? null;
        $secret_key = $_POST['secret_key'] ?? '';

        if (isset($_POST['add'])) {
            if (empty($licence_name) || empty($software_id) || empty($version_id) || empty($customer_id) || empty($start_date) || empty($end_date)) {
                $error = "All fields marked with * are required.";
            } else {
                // Generate a new secret key if not provided
                if (empty($secret_key)) {
                    $secret_key = generate_secret_key();
                }
                
                $stmt = $pdo->prepare(
                    "INSERT INTO licences (licence_name, software_id, version_id, customer_id, start_date, end_date, price, secret_key, message) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$licence_name, $software_id, $version_id, $customer_id, $start_date, $end_date, $price, $secret_key, $message]);
                $success = "Licence created successfully. Secret Key: " . e($secret_key);
            }
        } elseif (isset($_POST['update'])) {
            $id = $_POST['id'] ?? null;
            if (empty($id) || empty($licence_name) || empty($software_id) || empty($version_id) || empty($customer_id) || empty($start_date) || empty($end_date) || empty($secret_key)) {
                 $error = "All fields marked with * are required for update.";
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE licences SET licence_name = ?, software_id = ?, version_id = ?, customer_id = ?, 
                     start_date = ?, end_date = ?, price = ?, secret_key = ?, message = ?
                     WHERE id = ?"
                );
                $stmt->execute([$licence_name, $software_id, $version_id, $customer_id, $start_date, $end_date, $price, $secret_key, $message, $id]);
                $success = "Licence updated successfully.";
            }
        }
    }

    // Handle DELETE request
    if ($action === 'delete' && $id) {
         $stmt = $pdo->prepare("DELETE FROM licences WHERE id = ?");
         $stmt->execute([$id]);
         $success = "Licence deleted successfully.";
    }

    // Fetch data for 'edit' form
    $licence_to_edit = null;
    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare("SELECT * FROM licences WHERE id = ?");
        $stmt->execute([$id]);
        $licence_to_edit = $stmt->fetch();
    }

    // Fetch related data for dropdowns
    $softwares = $pdo->query("SELECT id, name FROM softwares ORDER BY name")->fetchAll();
    $customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
    
    // Fetch all versions for the (dynamic) dropdown
    // We'll fetch this with JS, but have a fallback
    $all_versions = $pdo->query("SELECT v.id, name, v.version_number FROM versions v JOIN softwares s ON v.software_id = s.id ORDER BY s.name, v.version_number")
                       ->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
                       
    // Fetch versions grouped by software ID for JS
    $versions_by_software = [];
    $stmt = $pdo->query("SELECT id, software_id, version_number FROM versions ORDER BY version_number");
    while ($row = $stmt->fetch()) {
        $versions_by_software[$row['software_id']][] = ['id' => $row['id'], 'version' => $row['version_number']];
    }


    // Fetch all licences with joins
    $licences = $pdo->query("
        SELECT 
            l.*, 
            s.name as software_name,
            v.version_number,
            c.name as customer_name, v.id
        FROM licences l
        JOIN softwares s ON l.software_id = s.id
        JOIN versions v ON l.version_id = v.`id`
        JOIN customers c ON l.customer_id = c.`id`
        ORDER BY l.end_date DESC
    ")->fetchAll();

} catch (PDOException $e) {
    var_dump($e);
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
        <p><?php echo $success; // Allow secret key HTML ?></p>
    </div>
<?php endif; ?>

<div class="mb-6 bg-gray-50 p-4 rounded-lg shadow-sm" x-data="licenceForm()">
    <h2 class="text-lg font-medium text-gray-900 mb-4">
        <?php echo $action === 'edit' ? 'Edit Licence' : 'Add New Licence'; ?>
    </h2>
    <form action="index.php?page=licences" method="POST">
        <?php if ($action === 'edit' && $licence_to_edit): ?>
            <input type="hidden" name="id" value="<?php echo e($licence_to_edit['id']); ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <label for="licence_name" class="block text-sm font-medium text-gray-700">Licence Name *</label>
                <input type="text" name="licence_name" id="licence_name" required
                       value="<?php echo e($licence_to_edit['licence_name'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="customer_id" class="block text-sm font-medium text-gray-700">Customer *</label>
                <select name="customer_id" id="customer_id" required
                        class="mt-1 block w-full">
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo e($customer['id']); ?>" 
                            <?php echo (isset($licence_to_edit['customer_id']) && $licence_to_edit['customer_id'] == $customer['id']) ? 'selected' : ''; ?>>
                            <?php echo e($customer['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="software_id" class="block text-sm font-medium text-gray-700">Software *</label>
                <select name="software_id" id="software_id" required
                        class="mt-1 block w-full">
                    <option value="">-- Select Software --</option>
                    <?php foreach ($softwares as $software): ?>
                        <option value="<?php echo e($software['id']); ?>"
                            <?php echo (isset($licence_to_edit['software_id']) && $licence_to_edit['software_id'] == $software['id']) ? 'selected' : ''; ?>>
                            <?php echo e($software['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="version_id" class="block text-sm font-medium text-gray-700">Version *</label>
                <select name="version_id" id="version_id" required
                        class="mt-1 block w-full">
                    <option value="">-- Select Software First --</option>
                    </select>
            </div>
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
                <input type="number" step="0.01" name="price" id="price"
                       value="<?php echo e($licence_to_edit['price'] ?? '0.00'); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date *</label>
                <input type="date" name="start_date" id="start_date" required
                       value="<?php echo e($licence_to_edit['start_date'] ?? date('Y-m-d')); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date *</label>
                <input type="date" name="end_date" id="end_date" required
                       value="<?php echo e($licence_to_edit['end_date'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div class="md:col-span-3">
                <label for="message" class="block text-sm font-medium text-gray-700">Inactive/Expired Message (optional)</label>
                <textarea name="message" id="message" rows="2"
                          placeholder="e.g., Your license has expired. Please contact support."
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo e($licence_to_edit['message'] ?? ''); ?></textarea>
            </div>

             <div class="md:col-span-3">
                <label for="secret_key" class="block text-sm font-medium text-gray-700">Secret Key *</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="text" name="secret_key" id="secret_key" required
                           value="<?php echo e($licence_to_edit['secret_key'] ?? ''); ?>"
                           placeholder="<?php echo $action !== 'edit' ? 'Leave blank to auto-generate' : ''; ?>"
                           class="block w-full flex-1 rounded-none rounded-l-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <button type="button" @click="generateKey()" class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 hover:bg-gray-100">
                        Generate
                    </button>
                </div>
            </div>
        </div>
        <div class="mt-6">
            <?php if ($action === 'edit'): ?>
                <button type="submit" name="update" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Licence
                </button>
                <a href="index.php?page=licences" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
            <?php else: ?>
                <button type="submit" name="add" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Add Licence
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div x-data="{ search: '' }">
    <h2 class="text-lg font-medium text-gray-900 mb-4">Existing Licences</h2>

    <div class="mb-4">
        <label for="searchLicences" class="sr-only">Search Licences</label>
        <input type="search" name="search" id="searchLicences" x-model.debounce.300ms="search" 
               placeholder="Search licences by name, customer, software, version, or key..."
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
    </div>

    <div class="overflow-x-auto shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Licence / Customer</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Software / Version</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validity</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Secret Key</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($licences)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No licences found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($licences as $licence): ?>
                        <?php
                            $today = new DateTime();
                            $end_date = new DateTime($licence['end_date']);
                            $is_active = $end_date >= $today;
                            // Create a searchable string
                            $searchable_data = strtolower(implode(' ', [
                                $licence['licence_name'],
                                $licence['customer_name'],
                                $licence['software_name'],
                                $licence['version_number'],
                                $licence['secret_key']
                            ]));
                        ?>
                    <tr x-show="search.trim() === '' || '<?php echo e($searchable_data); ?>'.includes(search.toLowerCase())">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($is_active): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo e($licence['licence_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo e($licence['customer_name']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo e($licence['software_name']); ?></div>
                            <div class="text-sm text-gray-500">v<?php echo e($licence['version_number']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo e(date('M j, Y', strtotime($licence['start_date']))); ?> to
                            <?php echo e(date('M j, Y', strtotime($licence['end_date']))); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono" title="<?php echo e($licence['secret_key']); ?>">
                            <?php echo e(substr($licence['secret_key'], 0, 10)); ?>...
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="index.php?page=licences&action=edit&id=<?php echo e($licence['id']); ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <a href="index.php?page=licences&action=delete&id=<?php echo e($licence['id']); ?>" 
                               class="text-red-600 hover:text-red-900 ml-4"
                               onclick="return confirm('Are you sure you want to delete this licence?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function licenceForm() {
        // This data will be injected by PHP
        const versionsData = <?php echo json_encode($versions_by_software); ?>;
        
        // Data for the currently editing licence (if any)
        const editData = <?php echo json_encode($licence_to_edit); ?>;

        return {
            versionsData: versionsData,
            selectedSoftware: editData ? editData.software_id : '',
            selectedVersion: editData ? editData.version_id : '',
            
            // Tom Select instances
            customerSelect: null,
            softwareSelect: null,
            versionSelect: null,

            init() {
                const tomSelectSettings = {
                    create: false,
                    sortField: { field: "text", direction: "asc" }
                };

                // 1. Initialize Tom Select instances
                this.customerSelect = new TomSelect('#customer_id', tomSelectSettings);
                this.softwareSelect = new TomSelect('#software_id', tomSelectSettings);
                this.versionSelect = new TomSelect('#version_id', tomSelectSettings);

                // 2. Set up event listener for software change
                this.softwareSelect.on('change', (value) => {
                    this.selectedSoftware = value;
                    this.updateVersions(value);
                });

                // 3. Set initial values if editing
                if (editData) {
                    if (editData.customer_id) {
                        this.customerSelect.setValue(editData.customer_id);
                    }
                    if (this.selectedSoftware) {
                        this.softwareSelect.setValue(this.selectedSoftware);
                        // Populate versions *then* set the value
                        this.populateVersions(this.selectedSoftware, this.selectedVersion);
                    } else {
                         this.versionSelect.disable(); // Disable if no software selected
                    }
                } else {
                     this.versionSelect.disable(); // Disable on new form
                }
            },

            updateVersions(softwareId) {
                this.populateVersions(softwareId, null);
            },
            
            populateVersions(softwareId, defaultVersionId) {
                const ts = this.versionSelect;
                ts.clear();       // Clears selected value
                ts.clearOptions(); // Clears all options
                
                const versions = this.versionsData[softwareId] || [];
                
                if (versions.length === 0) {
                    ts.addOption({ value: '', text: '-- No versions for this software --' });
                    ts.disable();
                } else {
                    ts.addOption({ value: '', text: '-- Select Version --' });
                    versions.forEach(v => {
                        ts.addOption({
                            value: v.id,
                            text: v.version
                        });
                    });
                    ts.enable();
                }
                
                if (defaultVersionId) {
                    ts.setValue(defaultVersionId);
                } else {
                    ts.setValue(''); // Set to placeholder
                }
            },

            async generateKey() {
                const buffer = new Uint8Array(16); // 16 bytes = 32 hex chars
                window.crypto.getRandomValues(buffer);
                const key = Array.from(buffer, byte => byte.toString(16).padStart(2, '0')).join('');
                document.getElementById('secret_key').value = key;
            }
        }
    }
</script>