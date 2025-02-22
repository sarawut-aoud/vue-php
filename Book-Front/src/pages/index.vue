<script setup>
import { ref,onMounted } from 'vue'
import { Notivue, Notification, push } from 'notivue'
import GallaryItems from '@/components/GallaryItems.vue'
import api from '/utils/axios';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';
const theme = ref('light')

const catelists = ref([]);
const getList = async ()=>{
          let {data} = await api.get('/api/category/getList')
          if(data?.data.length>0){
            catelists.value = data.data
          }
};
onMounted(()=>{
  getList()
});
const getTheme=(e)=>{
    theme.value = e
};
</script>

<template>
  <v-responsive class="border rounded ">
    <v-app :theme="theme" class="position-relative">
      <App-header  @themes="getTheme"></App-header>
      <v-navigation-drawer style="padding-top:30px;">
        <v-list>
          <v-list-subheader class="d-flex justify-center"> หมวดหมู่</v-list-subheader>
          <v-list-item v-for="(item, i) in catelists" :key="i" :value="item" color="primary" rounded="xl">
            <template v-slot:prepend>
              <v-icon icon="mdi-book-open-page-variant"></v-icon>
            </template>
            <v-list-item-title>{{ item.name }}</v-list-item-title>
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

      <AppFooter />

    </v-app>
  </v-responsive>

</template>
