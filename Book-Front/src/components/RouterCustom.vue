<template>
    <router-link class="text-decoration-none " :class="{'text-black':props.theme=='light','text-white':props.theme=='dark'}" :to="props.path">
        <slot name="default"></slot>
    </router-link>
</template>
<script setup>
import { defineProps } from 'vue';
const props =defineProps({
    path:String,
    theme:String,
});
</script>