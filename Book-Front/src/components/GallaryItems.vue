<template>
    <v-card>
        <v-tabs v-model="tab" color="info">
          <v-tab :value="1">Landscape</v-tab>
          <v-tab :value="2">City</v-tab>
          <v-tab :value="3">Abstract</v-tab>
        </v-tabs>

        <v-tabs-window v-model="tab">
          <v-tabs-window-item v-for="n in 3" :key="n" :value="n">
            <v-container fluid>
              <v-row>
                <v-col v-for="i in 20" :key="i" cols="12" md="2">
                  <v-img :lazy-src="`https://picsum.photos/10/6?image=${i * n * 5 + 10}`"
                    :src="`https://picsum.photos/500/300?image=${i * n * 5 + 10}`" height="205" cover></v-img>
                </v-col>
              </v-row>
            </v-container>
          </v-tabs-window-item>
        </v-tabs-window>
      </v-card>
</template>

<script>
 export default {
    data: () => ({
      tab: null,
    }),
    
  }
</script>
