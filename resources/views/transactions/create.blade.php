<!DOCTYPE html>
<html>
<head>
    <title>Add Transaction</title>
</head>
<body>
    <h2>Add New Transaction</h2>

    @if ($errors->any())
        <div style="color:red">
            <ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
        </div>
    @endif

    <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <label>Description:</label><br>
        <input type="text" name="description" required><br><br>

        <label>Amount:</label><br>
        <input type="number" step="0.01" name="amount" required><br><br>

        <label>Type:</label><br>
        <select name="type">
            <option value="expense">Expense</option>
            <option value="income">Income</option>
        </select><br><br>

        <label>Receipt (Image):</label><br>
        <input type="file" name="receipt_image" accept="image/*"><br><br>

        <button type="submit">Save Transaction</button>
    </form>
    
    <br>
    <a href="{{ route('transactions.index') }}">Back to Dashboard</a>
</body>
</html>