import { defineStore } from 'pinia'
import api, { setAuthToken } from '../api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('auth_token') || '',
    user: null,
    loading: false,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token),
  },
  actions: {
    init() {
      if (this.token) {
        setAuthToken(this.token)
      }
    },
    async register(payload) {
      this.loading = true
      try {
        const { data } = await api.post('/auth/register', payload)
        this.token = data.token
        this.user = data.user
        localStorage.setItem('auth_token', this.token)
        setAuthToken(this.token)
      } finally {
        this.loading = false
      }
    },
    async login(payload) {
      this.loading = true
      try {
        const { data } = await api.post('/auth/login', payload)
        this.token = data.token
        this.user = data.user
        localStorage.setItem('auth_token', this.token)
        setAuthToken(this.token)
      } finally {
        this.loading = false
      }
    },
    async fetchMe() {
      if (!this.token) return
      setAuthToken(this.token)
      const { data } = await api.get('/auth/me')
      this.user = data.user
    },
    async logout() {
      try {
        if (this.token) await api.post('/auth/logout')
      } finally {
        this.token = ''
        this.user = null
        localStorage.removeItem('auth_token')
        setAuthToken('')
      }
    },
  },
})

