<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - ممنوع الوصول</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            max-width: 600px;
            text-align: center;
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .error-icon {
            font-size: 6rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 30px;
        }
        .back-btn {
            background-color: #0d6efd;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-ban"></i>
        </div>
        <h1 class="error-title">403 - ممنوع الوصول</h1>
        <p class="error-message">
            {{ isset($exception) ? $exception->getMessage() : 'يمكن الوصول إلى هذه الصفحة من داخل الشركة فقط. يرجى التأكد من أنك متصل بشبكة الشركة والمحاولة مرة أخرى.' }}
        </p>
        <a href="{{ url('/') }}" class="btn back-btn">
            <i class="fas fa-home ml-2"></i> العودة إلى الصفحة الرئيسية
        </a>
    </div>
</body>
</html>
