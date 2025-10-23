<?php
// This file is included by index.php, so DB and auth are checked.

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

try {
    // Handle POST requests (Create, Update)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $siret = $_POST['siret'] ?? null;
        $address = $_POST['address'] ?? null;
        $email = $_POST['email'] ?? null;
        $phone = $_POST['phone'] ?? null;

        if (isset($_POST['add'])) {
            if (!empty($name)) {
                $stmt = $pdo->prepare("INSERT INTO customers (name, siret, address, email, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $siret, $address, $email, $phone]);
                $success = "Customer added successfully.";
            } else {
                $error = "Customer name is required.";
            }
        } elseif (isset($_POST['update'])) {
            $id = $_POST['id'] ?? null;
            if (!empty($id) && !empty($name)) {
                $stmt = $pdo->prepare("UPDATE customers SET name = ?, siret = ?, address = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $siret, $address, $email, $phone, $id]);
                $success = "Customer updated successfully.";
            } else {
                $error = "ID and Name are required for update.";
            }
        }
    }

    // Handle DELETE request
    if ($action === 'delete' && $id) {
         try {
             // Check for related licences first
             $stmt = $pdo->prepare("SELECT COUNT(*) FROM licences WHERE customer_id = ?");
             $stmt->execute([$id]);
             if ($stmt->fetchColumn() > 0) {
                 $error = "Cannot delete customer. They have existing licences. Delete the licences first.";
             } else {
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Customer deleted successfully.";
             }
         } catch (PDOException $e) {
             $error = "Could not delete customer. Check for related licences.";
         }
    }

    // Fetch data for 'edit' form
    $customer_to_edit = null;
    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer_to_edit = $stmt->fetch();
    }

    // Fetch all customers
    $customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();

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
        <?php echo $action === 'edit' ? 'Edit Customer' : 'Add New Customer'; ?>
    </h2>
    <form action="index.php?page=customers" method="POST">
        <?php if ($action === 'edit' && $customer_to_edit): ?>
            <input type="hidden" name="id" value="<?php echo e($customer_to_edit['id']); ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Customer Name *</label>
                <input type="text" name="name" id="name" required
                       value="<?php echo e($customer_to_edit['name'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email"
                       value="<?php echo e($customer_to_edit['email'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="tel" name="phone" id="phone"
                       value="<?php echo e($customer_to_edit['phone'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
             <div>
                <label for="siret" class="block text-sm font-medium text-gray-700">SIRET (optional)</label>
                <input type="text" name="siret" id="siret"
                       value="<?php echo e($customer_to_edit['siret'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="md:col-span-2">
                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                <textarea name="address" id="address" rows="3"
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo e($customer_to_edit['address'] ?? ''); ?></textarea>
            </div>
        </div>
        <div class="mt-6">
            <?php if ($action === 'edit'): ?>
                <button type="submit" name="update" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Customer
                </button>
                <a href="index.php?page=customers" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
            <?php else: ?>
                <button type="submit" name="add" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Add Customer
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div x-data="{ search: '' }">
    <h2 class="text-lg font-medium text-gray-900 mb-4">Existing Customers</h2>
    
    <div class="mb-4">
        <label for="searchCustomers" class="sr-only">Search Customers</label>
        <input type="search" name="search" id="searchCustomers" x-model.debounce.300ms="search" 
               placeholder="Search customers by name, contact, SIRET, or address..."
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
    </div>

    <div class="overflow-x-auto shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SIRET</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No customers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                    <?php 
                        // Create a searchable string
                        $searchable_data = strtolower(implode(' ', [
                            $customer['name'],
                            $customer['address'],
                            $customer['email'],
                            $customer['phone'],
                            $customer['siret']
                        ]));
                    ?>
                    <tr x-show="search.trim() === '' || '<?php echo e($searchable_data); ?>'.includes(search.toLowerCase())">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo e($customer['name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo e($customer['address']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo e($customer['email']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo e($customer['phone']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($customer['siret']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="index.php?page=customers&action=edit&id=<?php echo e($customer['id']); ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <a href="index.php?page=customers&action=delete&id=<?php echo e($customer['id']); ?>" 
                               class="text-red-600 hover:text-red-900 ml-4"
                               onclick="return confirm('Are you sure you want to delete this customer?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>