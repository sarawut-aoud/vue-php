<template>
  <v-responsive class="border rounded">
    <v-app :theme="theme">
      <v-app-bar title="ReRead" scroll-behavior="inverted" class="pa-2">
        <div class="me-2 d-flex ga-2 align-center">
          <v-btn :icon="theme === 'light' ? 'mdi-weather-sunny' : 'mdi-weather-night'" @click="onClick"></v-btn>
          <div>
            <v-badge :content="5" color="red">
              <v-btn icon="mdi-cart" @click="carts = !carts"></v-btn>
            </v-badge>
          </div>
        </div>
      </v-app-bar>
      <Carts-items :carts="carts"></Carts-items>

      <v-navigation-drawer style="padding-top:30px;">
        <v-list>
          <v-list-subheader class="d-flex justify-center"> Categorys</v-list-subheader>
          <v-list-item v-for="(item, i) in items" :key="i" :value="item" color="primary" rounded="xl">
            <template v-slot:prepend>
              <v-icon :icon="item.icon"></v-icon>
            </template>
            <v-list-item-title >{{item.text}}</v-list-item-title>
          </v-list-item>
        </v-list>
        <v-divider></v-divider>
      </v-navigation-drawer>
      <v-main style="padding-top:100px;">
        <div class="d-flex flex-column ga-2 ">
          <div>
            <v-card>
              <v-img height="200px" src="https://cdn.vuetifyjs.com/images/cards/sunshine.jpg" cover></v-img>

              <v-card-title>
                Top western road trips
              </v-card-title>

              <v-card-subtitle>
                1,000 miles of wonder
              </v-card-subtitle>

              <v-card-actions>
                <v-btn color="orange-lighten-2" text="Explore"></v-btn>

                <v-spacer></v-spacer>

                <v-btn :icon="show ? 'mdi-chevron-up' : 'mdi-chevron-down'" @click="show = !show"></v-btn>
              </v-card-actions>

              <v-expand-transition>
                <div v-show="show">
                  <v-divider></v-divider>

                  <v-card-text>
                    I'm a thing. But, like most politicians, he promised more than he could deliver. You won't have time
                    for
                    sleeping, soldier, not with all the bed making you'll be doing. Then we'll go with that data file!
                    Hey, you
                    add a one and two zeros to that or we walk! You're going to do his laundry? I've got to find a way
                    to
                    escape.
                  </v-card-text>
                </div>
              </v-expand-transition>
            </v-card>
          </div>
          <div>
            <Gallary-items></Gallary-items>
          </div>
        </div>

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
import GallaryItems from '@/components/GallaryItems.vue'
import CartsItems from '@/components/CartsItems.vue';
import axios from 'axios';  
 axios.create({  
    baseURL: 'http://localhost:8000/api',  
    headers: {  
        'Content-Type': 'application/json',  
    },  
});  
const theme = ref('light')
 export default {
  components: {
     GallaryItems,
     CartsItems
     },
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
    mounted(){
     
    },  
    methods:{
      onClick () {
        theme.value = theme.value === 'light' ? 'dark' : 'light'
      }
    }
  }
  </script>


<style>
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap');

* {
  font-family: "Noto Sans Thai", serif;
  font-optical-sizing: auto;
  font-weight: 500;
  font-style: normal;
}
</style>