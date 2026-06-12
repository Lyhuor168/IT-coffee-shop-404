import { createContext, useContext, useEffect, useState, useCallback } from 'react'
import api from '../api/axios'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [token, setToken] = useState(localStorage.getItem('token'))
  const [loading, setLoading] = useState(true)

  const fetchUser = useCallback(async () => {
    const res = await api.get('/auth/me')
    setUser(res.data.user)
    localStorage.setItem('user', JSON.stringify(res.data.user))
    return res.data.user
  }, [])

  useEffect(() => {
    const init = async () => {
      if (localStorage.getItem('token')) {
        try {
          await fetchUser()
        } catch {
          localStorage.removeItem('token')
          localStorage.removeItem('user')
          setToken(null)
        }
      }
      setLoading(false)
    }
    init()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const login = async (email, password) => {
    const res = await api.post('/auth/login', { email, password })
    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))
    setToken(res.data.token)
    setUser(res.data.user)
    return res.data.user
  }

  const register = async (payload) => {
    const res = await api.post('/auth/register', payload)
    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))
    setToken(res.data.token)
    setUser(res.data.user)
    return res.data.user
  }

  const logout = async () => {
    try {
      await api.post('/auth/logout')
    } catch {
      // ignore
    } finally {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      setToken(null)
      setUser(null)
    }
  }

  const updateUser = (u) => {
    setUser(u)
    localStorage.setItem('user', JSON.stringify(u))
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        loading,
        isAuthenticated: !!token && !!user,
        isAdmin: user?.role === 'admin',
        login,
        register,
        logout,
        updateUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
