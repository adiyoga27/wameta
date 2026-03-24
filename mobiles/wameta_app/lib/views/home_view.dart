import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/chat_models.dart';
import '../services/api_service.dart';
import '../providers/auth_provider.dart';
import 'chat_list_view.dart';

class HomeView extends StatefulWidget {
  const HomeView({super.key});

  @override
  State<HomeView> createState() => _HomeViewState();
}

class _HomeViewState extends State<HomeView> {
  final _api = ApiService();
  late Future<List<DeviceModel>> _devicesFuture;

  @override
  void initState() {
    super.initState();
    _devicesFuture = _fetchDevices();
  }

  Future<List<DeviceModel>> _fetchDevices() async {
    final res = await _api.getDevices();
    if (res.statusCode == 200) {
      return (res.data as List).map((e) => DeviceModel.fromJson(e)).toList();
    }
    throw Exception('Failed to load devices');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pilih Akun WhatsApp', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => context.read<AuthProvider>().logout(),
          ),
        ],
      ),
      body: FutureBuilder<List<DeviceModel>>(
        future: _devicesFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          }
          final devices = snapshot.data ?? [];
          if (devices.isEmpty) {
            return const Center(child: Text('Belum ada perangkat yang terhubung.'));
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: devices.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final device = devices[index];
              return Card(
                color: const Color(0xFF1E272E),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                  title: Text(device.name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18)),
                  subtitle: Row(
                    children: [
                      Icon(Icons.circle, size: 8, color: device.status == 'connected' ? Colors.green : Colors.red),
                      const SizedBox(width: 6),
                      Text(device.status.toUpperCase(), style: const TextStyle(color: Colors.white70, fontSize: 12)),
                    ],
                  ),
                  trailing: const Icon(Icons.chevron_right, color: Colors.white38),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => ChatListView(device: device)),
                    );
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }
}
