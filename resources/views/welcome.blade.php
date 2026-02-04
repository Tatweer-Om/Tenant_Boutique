<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" 
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body>
  <div class="container mt-5">

    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <a class="btn btn-primary" href="{{ route('tenants.index') }}">Tenants</a>
      
      <div>
        <span class="me-3">Welcome, {{ session('username') }}</span>
        <a href="{{ route('logout') }}" class="btn btn-outline-danger btn-sm">Logout</a>
      </div>
    </div>

    <!-- Users Table -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Users List</h5>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Tenant</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $index => $user)
            <tr>
              <td>{{ $index + 1 }}</td>
              <td>{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->tenant->name ?? '-' }}</td>
              <td>
                <a href="#" class="btn btn-sm btn-warning">Edit</a>
                <a href="#" class="btn btn-sm btn-danger">Delete</a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center">No users found</td>
            </tr>
            @endforelse
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="d-flex justify-content-end">
          {{ $users->links() }}
        </div>
      </div>
    </div>

  </div>
 
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
          integrity="sha384-cVKIPhGWiC2d7oG3QzvV3g5NJd61xq3O3zZz9q3GxG+Xy4eGp2Zn2fPJjSnj2d41" crossorigin="anonymous"></script>
</body>
</html>
