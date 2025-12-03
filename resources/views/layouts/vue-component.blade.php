<div class="{{ $rootClasses }}" 
     data-controller="vue-component"
     data-vue-component-name-value="{{ $component }}"
     data-vue-component-props-value="{{ json_encode($props) }}">
</div>

@push('scripts')
<script type="module">
import { createApp } from 'vue';

document.addEventListener('DOMContentLoaded', () => {
    const elements = document.querySelectorAll('[data-controller="vue-component"]');
    
    elements.forEach(element => {
        const componentName = element.dataset.vueComponentNameValue;
        const props = JSON.parse(element.dataset.vueComponentPropsValue || '{}');
        
        // Dynamically import component
        import(`@/${componentName}.vue`).then(module => {
            const app = createApp(module.default, props);
            app.mount(element);
        }).catch(err => {
            console.error(`Failed to load component: ${componentName}`, err);
        });
    });
});
</script>
@endpush

