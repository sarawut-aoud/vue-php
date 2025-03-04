<template>
    <div class="d-flex flex-column ga-2 w-100 align-center">
        <div class="w-100 d-flex align-center ga-2">
            <v-card class="w-100" title="ราคาหนังสือ" solo border rounded="lg">

            </v-card>
            <v-card class="w-100" title="ราคาหนังสือ" solo border rounded="lg">

            </v-card>
        </div>
        <div class="w-100">
            <v-card title="หนังสือ" solo border rounded="lg">
                <v-card-text>
                    <template v-slot:text>
                        <v-text-field v-model="search" label="Search" prepend-inner-icon="mdi-magnify"
                            variant="outlined" hide-details single-line></v-text-field>
                    </template>
                    <div class="d-flex flex-column ga-2">
                        <div class="d-flex w-100 align-center justify-space-between">
                            <div class="w-50">
                                <v-text-field hide-details="" v-model="search" placeholder="ค้นหา..." variant="outlined"
                                    density="comfortable" prepend-inner-icon="mdi-magnify"></v-text-field>
                            </div>
                            <div class=" d-flex align-end ">
                                <v-btn @click="dialog = true; ref_id = null; name = null;
                                no = null" color="primary">เพิ่มหนังสือ</v-btn>
                            </div>
                        </div>
                        <v-data-table :headers="headers" :items="catelists" :search="search">
                            <template v-slot:item.manage="{ item }">
                                <div class="d-flex align-center ga-2">
                                    <v-btn @click="getListById(item._i)" icon="mdi-pencil" size="small" rounded="lg"
                                        variant="flat" color="warning"></v-btn>
                                    <v-btn @click="removeCategory(item._i)" icon="mdi-eraser" size="small" rounded="lg"
                                        variant="flat" color="red"></v-btn>
                                </div>
                            </template>
                        </v-data-table>
                    </div>

                </v-card-text>
            </v-card>
        </div>
    </div>
    <Notivue v-slot="item">
        <Notification :item="item" />
    </Notivue>

    <v-dialog v-model="dialog" width="800">
        <v-card>
            <v-card-text class="d-flex flex-column ga-3">
                <v-text-field v-model="name" hide-details="" placeholder="ชื่อหมวดหมู่" variant="outlined"
                    density="comfortable"></v-text-field>
                <v-text-field v-model="no" hide-details="" placeholder="รหัสหมวดหมู่" variant="outlined"
                    density="comfortable"></v-text-field>
            </v-card-text>
            <template v-slot:actions>
                <v-btn variant="flat" class="ms-auto" text="ปิด" @click="dialog = false"></v-btn>
                <v-btn @click="update()" v-if="ref_id" variant="flat" text="บันทึกการแก้ไข" color="success"></v-btn>
                <v-btn @click="create()" v-if="!ref_id" variant="flat" text="บันทึก" color="success"></v-btn>
            </template>
        </v-card>
    </v-dialog>

</template>
<script setup>
import {ref,onMounted } from 'vue';
import { Notivue, Notification, push } from 'notivue'

import api from '/utils/axios';
import {useCookie} from '@/composables/useCookie';
const catelists = ref([]);
const search = ref('');
const {getCookie} =useCookie()
const dialog = ref(false);
const name =ref(null)
const no =ref(null)
const ref_id =ref(null)
const  headers = ref([
          {
            align: 'start',
            key: 'name',
            title: 'ชื่อหมวดหมู่',
          },
          { key: 'no', title: 'รหัสหมวดหมู่' },
          { key: 'manage', title: '#' },
        ]);
 const getList = async ()=>{
          let {data} = await api.get('/api/category/getList')
          if(data?.data.length>0){
            catelists.value = data.data
          }
};

const removeCategory=async (id)=>{
    
   await api.post('/api/category/remove/'+id,{
             csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen")
        }).then((result)=>{
          return result.data
        }).then((data)=>{
            if(data.status){
                push.success('ลบข้อมูลสำเร็จ')
                getList()
            }
        })
         
}
const getListById =async (id)=>{
    await api.get('/api/category/getListById/'+id,{
        }).then((result)=>{
          return result.data
        }).then((data)=>{
            if(data.status){
              ref_id.value = data?.data?._i
              name.value =data?.data?.name;
              no.value = data?.data?.no;
              dialog.value = true
            }
        })
}
const update = async()=>{
    await api.post('/api/category/update',{
                id:ref_id.value,
                name:name.value,
                no:no.value,
                csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen")
        }).then((result)=>{
          return result.data
        }).then((data)=>{
            if(data.status){
                push.success('บันทึกสำเร็จ')
                getList()
                resetData()
            }
        })
}
const create = async()=>{
    await api.post('/api/category/create',{
                name:name.value,
                no:no.value,
             csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen")
        }).then((result)=>{
          return result.data
        }).then((data)=>{
            if(data.status){
                push.success('บันทึกสำเร็จ')
                getList()
                resetData()
            }
        })
}
const resetData=()=>{
    name.value =null;
    no.value = null;
    ref_id.value = null;
    dialog.value =false

}
onMounted(()=>{
  getList()
});
</script>