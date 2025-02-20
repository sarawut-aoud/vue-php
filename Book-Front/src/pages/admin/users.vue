<template>
    <v-responsive class="border rounded">
      <v-app :theme="theme">
        <v-app-bar title="BOOK CENTER : ADMIN" scroll-behavior="inverted" class="pa-2">
          <div class="me-2 d-flex ga-2 align-center">
            <v-btn :icon="theme === 'light' ? 'mdi-weather-sunny' : 'mdi-weather-night'" @click="onClick"></v-btn>
            <div>
            </div>
          </div>
        </v-app-bar>
  
        <v-navigation-drawer style="padding-top:30px;">
          <v-list>
            <v-list-subheader class="d-flex justify-center"> Categorys</v-list-subheader>
            <v-list-item v-for="(item, i) in items" :key="i" :value="item" color="primary" rounded="xl">
              <template v-slot:prepend>
                <v-icon :icon="item.icon"></v-icon>
              </template>
              <v-list-item-title>{{item.text}}</v-list-item-title>
            </v-list-item>
          </v-list>
          <v-divider></v-divider>
        </v-navigation-drawer>
        <v-main style="padding-top:100px;">
  
        </v-main>
      </v-app>
    </v-responsive>
    <v-footer border>
      <div class=" w-100 d-flex justify-end">
        2025 — Vuetify, LLC
      </div>
    </v-footer>
  </template>
  
  
  <script>
  import { ref } from 'vue'
  const theme = ref('light')
   export default {
      data: () => ({
        theme:theme,
        carts: false,
        show:false,
        items: [
          { text: 'Real-Time', icon: 'mdi-clock' },
          { text: 'Audience', icon: 'mdi-account' },
          { text: 'Conversions', icon: 'mdi-flag' },
        ],
      }),
      methods:{
        onClick () {
          theme.value = theme.value === 'light' ? 'dark' : 'light'
        }
      }
    }
    </script>