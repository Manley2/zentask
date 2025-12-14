<button {{ $attributes->merge([
    'class' => 'bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg shadow-lg transform hover:scale-105 transition-all duration-200'
]) }}>
    {{ $slot }}
</button>
```

